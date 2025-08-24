<?php

require 'Parser.php';
require 'ParserInterface.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\DomCrawler\Crawler;

class Vesta extends Parser implements ParserInterface
{
    private $urls = [
        'https://vesta.am/herustacuycner?tf_ff=23.28.91.87&limit=1000',
        'https://vesta.am/sarnaranner-sarcaranner?tf_ff=23.28.91.87&limit=1000',
        'https://vesta.am/manr-kencax?tf_ff=23.28.91.87&limit=1000',
        'https://vesta.am/lvacqi-meqenaner-choranocner?tf_ff=23.28.91.87&limit=1000',
        'https://vesta.am/nerkarucvogh-texnika?tf_ff=23.28.91.87&limit=1000',
    ];

    public string $name = "vesta";

    function start()
    {
        foreach ($this->urls as $k => $url) {
            $this->log($url);
            $crawler = $this->getHtml($url);
            $products = [];
            $crawler->filter(".products")->first()
                ->filter('.product-description')
                ->each(function (Crawler $node) use(&$products) {
                $products[] = $node->filter('a')->attr('href')."\n";
            });
            $this->saveJson($products,$k.'.json');
            foreach ($products as $index => $product) {
                $path = $k."/".$index.".html";
                $this->saveHtml($product,$path);
                $this->log($k."\t".$index." of ".count($products));
            }
        }
    }
}

Vesta::run();