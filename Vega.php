<?php

require 'Parser.php';
require 'ParserInterface.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\DomCrawler\Crawler;

class Vega extends Parser implements ParserInterface
{
    private $urls = [
        'https://vega.am/am/aowdio-video-tekhnika/herhowstatsowytsner/',
        'https://vega.am/am/geghetskowtyown-ew-khnamk/',
        'https://vega.am/am/khoshor-kentsaghayin/',
        'https://vega.am/am/khohanots-ev-town/',
        'https://vega.am/am/kentsaghayin-tekhnika/',
    ];
    private $mans = "?mfp=manufacturers[124,80,150,17,326]";

    public string $name = "vega";


    function start()
    {
//        $this->getProductHtmls();
        $products = [];
        $dir = "htmls/vega/";
        $htmls = [];
        $files = scandir($dir);
        foreach ($files as $file){
            if($file != "." && $file != ".." && is_dir($dir.$file)){
                foreach (scandir($dir.$file) as $f){
                    if(is_file($dir.$file."/".$f) && str_ends_with($f,".html")){
                        $htmls[] = $dir.$file."/".$f;
                    }
                }
            }
        }
        foreach ($htmls as $k => $html){
            $this->log(($k + 1)." of ".count($htmls));
            $product = [];
            $crawler = new Crawler(file_get_contents($html));
            $lis = $crawler->filter('.breadcrumb')->filter('li')
                ->each(function (Crawler $node){
                    return $node->text();
                });
            $product['name'] = array_pop($lis);
            $product['cateogory'] = $lis;
            $product['images'] = $crawler->filter('.product-image-left')->first()->filter('a')
                ->each(function (Crawler $node){
                    return $node->attr('href');
                });
            $product['images'] = array_unique($product['images']);
            $price_old = $crawler->filter('#content')->filter('#price-old');
            if($price_old->count() > 0){
                $product['price_old'] = $price_old->first()->text();
            }
            $product['price'] = $crawler->filter('#content')
                ->filter('#price-special')->text();
            $attributes = [];
            $attr_group = "";
            $crawler->filter('.attribute')->filter('tr')
                ->each(function (Crawler $tr) use(&$attributes,&$attr_group){
                    $tds = $tr->filter('td');
                    if($tds->count() == 1){
                        $attr_group = $tds->first()->text();
                    }else{
                        $attributes[$attr_group][$tds->first()->text()] = $tds->last()->text();
                    }
                });
            $product['attributes'] = $attributes;
            $product['url'] = $crawler->filter('link[rel="canonical"]')->first()->attr('href');
            $u = explode('/', $product['url']);
            $product['sku'] = str_replace('.html','',end($u));
            try {
                $this->saveimages($product['sku'],$product['images']);
            }catch (\Exception $e){
                $this->error($e->getMessage());
            }
            $products[] = $product;
        }
        $this->saveJson($products,"products.json");
    }

    function getProductHtmls()
    {
        foreach ($this->urls as $k => $url) {
            $products = $this->paginate($url);
            $this->saveJson($products,$k.'.json');
            foreach ($products as $index => $product) {
                $path = $k."/".$index.".html";
                $this->saveHtml($product,$path);
                $this->log($index." of ".count($products));
            }
        }
    }

    function paginate($url)
    {
        $this->log($url);
        $crawler = $this->getHtml($url);
        if($crawler->filter('.category-list')->count() == 1){
            $category_urls = [];
            $crawler->filter('.category-list')->filter('a')->each(function (Crawler $node, $i) use(&$category_urls){
                $category_urls[] = $node->attr('href');
            });
            $category_urls = array_unique($category_urls);
            $products_urls = [];
            foreach ($category_urls as $category_url) {
                $products_urls = array_merge($products_urls,$this->paginate($category_url));
                $this->log('count = '.count($products_urls));
            }
            return $products_urls;
        }
        $url = $url.$this->mans;
        $pages = [$url];
        try{
            $href = $crawler->filter('.pagination')->first()->filter('li')->last()->filter('a')->first()->attr('href');
            if (preg_match('~page-([0-9]+)~', $href, $matches)) {
                $lastPage = (int)$matches[1];
                if(strpos($url, '?') !== false){
                    $e = explode('?', $url);
                    $e[1] = "?".$e[1];
                }else{
                    $e[0] = $url;
                    $e[1] = '';
                }
                for($i = 2;$i <= $lastPage; $i++) {
                    $pages[] = $e[0]."/page-".$i.$e[1];
                }
            }
        }catch (\Exception $e){
            print_r("no pagination");
        }
        $products_urls = [];
        foreach ($pages as $page) {
            $crawler = $this->getHtml($page);
            $crawler->filter('.product-grid')->first()->filter('.product')->each(function (Crawler $node) use (&$products_urls) {
                $products_urls[] = $node->filter('.right')->filter('a')->attr('href');
            });
        }
        return $products_urls;
    }
}

Vega::run();