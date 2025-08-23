<?php

require 'Parser.php';
require 'ParserInterface.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\DomCrawler\Crawler;

class VLV extends Parser implements ParserInterface
{
    public string $name = "vlv";

    private $brands = [
        '261',
        '309',
        '325',
        '289',
        '204',
    ];
    private $urls = [];

    function start()
    {
        $this->urls = json_decode(file_get_contents("vlv_cateogry_slug.json"), true);
//        $this->categories();
        foreach ($this->urls as $i => $sku){
            $json = "htmls/vlv/".$sku.".json";
            $path = $sku."/";
            $arr = json_decode(file_get_contents($json), true);
            foreach ($arr as $k => $id){
                $this->log($sku."\t".$id."\t\t".$i.'/'.count($this->urls)."\t\t".$k.'/'.count($arr));
                $json = $this->getJson('https://vlv.am/api/product-info/'.$id,"POST");
                $this->saveJson($json,$path.$id.'.json');;
            }
        }
    }

    function categories()
    {
        foreach ($this->urls as $slug) {
            $products = [];
            $this->log($slug);
            $arr = $this->getJson('https://v1.vlv.am/api/category/' . $slug,'POST',[
                'form_params' => [
                    'slug' => $slug,
                    'p' => '1000'
                ]
            ]);
            foreach ($arr['products'] as $product) {
                if(isset($product['brand']['id']) && in_array($product['brand']['id'],$this->brands)){
                    $products[] = $product['seller_id'];
                }
            }
            $this->saveJson($products,$slug.'.json');
        }
    }
    function getProducts($cat_sku)
    {

    }
}

VLV::run();