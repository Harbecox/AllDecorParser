<?php

use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\DomCrawler\Crawler;

require 'vendor/autoload.php';
class Parser
{
    private Client $client;
    private Logger $logger;
    private string $base_path = "htmls/";

    public string $name = "";

    function __construct()
    {
        $this->logger = new Logger("parser");
        $stream_handler = new StreamHandler("php://stdout");
        $this->logger->pushHandler($stream_handler);
        $this->client = new Client();
    }

    function getHtml($url): Crawler
    {
        $response = $this->client->request('GET', $url);
        return new Crawler($response->getBody()->getContents());
    }

    function getJson($url,$method = 'GET',$payload = [])
    {
        $response = $this->client->request($method, $url,$payload);
        return json_decode($response->getBody()->getContents(), true);
    }

    function saveHtml($url,$path)
    {
        $path = $this->checkDir($path);
        $response = $this->client->request('GET', $url);
        file_put_contents($path, $response->getBody());
    }

    public function log($text){
        $this->logger->info($text);
    }

    function saveJson($arr,$path)
    {
        $path = $this->checkDir($path);
        file_put_contents($path, json_encode($arr, 256));
    }

    function checkDir($path)
    {
        if(!str_ends_with($this->name,"/")){
            $this->name .= "/";
        }
        $path = $this->base_path.$this->name.$path;
        $e = explode('/', $path);
        $file_name = array_pop($e);
        $path = "";
        foreach ($e as $dir) {
            $path .= $dir . '/';
            if(!is_dir($path)) {
                mkdir($path);
            }
        }
        $path .= $file_name;
        return $path;
    }

    static function run()
    {
        $parser = new static();
        $parser->start();
    }
}