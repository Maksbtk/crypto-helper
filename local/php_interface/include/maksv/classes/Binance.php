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

       /* $this->api_key = 'tWHcK2aAS7FoDYwGMdVR1Cy1wmlp9cpRdo5YFNSNH1NSxp7Q6l8c1ltWnfPPLMTf1'; # Input your API Key
        $this->secret_key = 'l5VZGyIs4wyz18KtwLJ5p6pvNeo0vkNl8yW1Effzk8JWAK7mNKGI5MLtc3lWsb611'; # Input your Secret Key*/

        $this->api_key = \Maksv\Keys::BINANCE_API_KEY;
        $this->secret_key = \Maksv\Keys::BINANCE_SECRET_KEY;

        //$this->url="https://data-api.binance.vision";
        $this->url = "https://api3.binance.com";
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

    public function tradesHistory($symbol, $limit = 10)
    {
        return $this->client->tradesHistory(
            $symbol,
            [
                'limit' => $limit,
            ]
        );
    }
    
}
