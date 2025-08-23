<?php

require 'Parser.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\DomCrawler\Crawler;

class Vega extends Parser
{
    private $mans = "?mfp=manufacturers[124,80,150,17,326]";
    private $urls = [
        'https://vega.am/am/aowdio-video-tekhnika/herhowstatsowytsner/',
        'https://vega.am/am/geghetskowtyown-ew-khnamk/',
        'https://vega.am/am/khoshor-kentsaghayin/',
        'https://vega.am/am/khohanots-ev-town/',
        'https://vega.am/am/kentsaghayin-tekhnika/',
    ];

    static function run()
    {
        $parser = new static();
        $parser->start();
    }

    function start()
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
            $this->log($page);
            $crawler = $this->getHtml($page);
            $crawler->filter('.product-grid')->first()->filter('.product')->each(function (Crawler $node) use (&$products_urls) {
                $products_urls[] = $node->filter('.right')->filter('a')->attr('href');
            });
        }
        return $products_urls;
    }
}

Vega::run();