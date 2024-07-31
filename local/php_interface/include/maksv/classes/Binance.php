<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Binance
{

    protected string $api_key;
    protected string $secret_key;
    protected string $url;
    protected \Binance\Spot $client;

    public function __construct(){

        $this->api_key='tWHcK2aAS7FoDYwGMdVR1Cy1wmlp9cpRdo5YFNSNH1NSxp7Q6l8c1ltWnfPPLMTf'; # Input your API Key
        $this->secret_key='l5VZGyIs4wyz18KtwLJ5p6pvNeo0vkNl8yW1Effzk8JWAK7mNKGI5MLtc3lWsb61'; # Input your Secret Key
        //$this->url="https://data-api.binance.vision";
        $this->url="https://api3.binance.com";
        $this->client = new \Binance\Spot(['key' => $this->curl, 'secret' => $this->secret_key]);
    }

    public function getSymbols()
    {
        return $this->client->exchangeInfo();
    }

    public function getAvgPrice($symbol)
    {
        return $this->client->avgPrice($symbol);
    }

    public function getDepth($symbol, $limit = 1)
    {
        return $this->client->depth(
            $symbol,
            [
                'limit' => $limit,
            ]
        );
    }



}
