<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Bybit
{

    protected string $api_key;
    protected string $secret_key;
    protected string $url;
    protected object $curl;

    public function __construct($apiKey = false, $secretKey = false)
    {
        if ($apiKey && $secretKey) {
            $this->api_key = $apiKey;
            $this->secret_key = $secretKey;
        } else {
            $this->api_key = \Maksv\Keys::BYBIT_API_KEY;
            $this->secret_key = \Maksv\Keys::BYBIT_SECRET_KEY;
        }

        //$this->url="https://api-testnet.bybit.com"; #Testnet environment
        $this->url="https://api.bybit.com";
    }
    
    public function openConnection()
    {
        $this->curl = curl_init();
    }

    public function closeConnection()
    {
        curl_close($this->curl);
    }

    public function httpReq($endpoint, $method, $params, $Info)
    {
        $res = [];
        $timestamp = time() * 1000;
        $params_for_signature= $timestamp . $this->api_key . "5000" . $params;
        $signature = hash_hmac('sha256', $params_for_signature, $this->secret_key);

        if($method=="GET")
            $endpoint=$endpoint . "?" . $params;

        curl_setopt_array($this->curl, array(
            CURLOPT_URL => $this->url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => array(
                "X-BAPI-API-KEY: $this->api_key",
                "X-BAPI-SIGN: $signature",
                "X-BAPI-SIGN-TYPE: 2",
                "X-BAPI-TIMESTAMP: $timestamp",
                "X-BAPI-RECV-WINDOW: 5000",
                "Content-Type: application/json"
            ),
           //CURLINFO_HEADER_OUT => true
        ));

        if($method == "GET")
            curl_setopt($this->curl, CURLOPT_HTTPGET, true);

        $response = curl_exec($this->curl);

        /*$res = [
            'info' => $Info,
            'response' => $response,
            'lastHttpCode' => curl_getinfo($this->curl)['http_code'],
        ];*/

        $res = $response;
        return $res;
    }

    public function getServerTime()
    {
        $endpoint = "/v5/market/time";
        $method = "GET";
        $params = "";
        return(json_decode($this->httpReq($endpoint, $method, $params, "symbols"),true));
        /*$res = json_decode($this->httpReq($endpoint, $method, $params, "symbols"),true);
        $res['api_key'] = $this->api_key;
        return $res;*/
    }

    public function getSymbols()
    {
        $endpoint = "/spot/v3/public/symbols";
        $method = "GET";
        $params = "";
        return json_decode($this->httpReq($endpoint, $method, $params, "symbols"),true);
    }

    public function getSymbolsV5($category = 'spot', $symbol = '')
    {
        $endpoint = "/v5/market/instruments-info";
        $method = "GET";
        $params="category=$category&symbol=$symbol";
        return json_decode($this->httpReq($endpoint, $method, $params, "symbols"),true);
    }

    public function getSpotPrice($symbol)
    {
        $endpoint="/spot/v3/public/quote/ticker/price";
        $method="GET";
        $params="symbol=$symbol";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol price"), true);
    }

    public function getDepth($symbol, $limit = 10)
    {
        $endpoint="/spot/v3/public/quote/depth";
        $method="GET";
        $params="symbol=$symbol&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol price list"), true);
    }

    public function getMergedDepth($symbol, $limit = 1, $scale = 1)
    {
        $endpoint="/spot/v3/public/quote/depth/merged";
        $method="GET";
        $params="symbol=$symbol&scale=$scale&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol price list"), true);
    }

    public function bestBidAsk($symbol)
    {
        $endpoint="/spot/v3/public/quote/ticker/bookTicker";
        $method="GET";
        $params="symbol=$symbol";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol price list"), true);
    }

    public function ticker($symbol)
    {
        $endpoint="/spot/v3/public/quote/ticker/24hr";
        $method="GET";
        $params="symbol=$symbol";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol pair full info"), true);
    }

    public function tradesHistory($symbol, $limit = 60)
    {
        $endpoint="/spot/v3/public/quote/trades";
        $method="GET";
        $params="symbol=$symbol&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol trades history"), true);
    }

    public function tradesHistoryV5($category = 'spot', $symbol = 'BTCUSDT', $limit = 60)
    {
        $endpoint="/v5/market/recent-trade";
        $method="GET";
        $params="category=$category&symbol=$symbol&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol trades history"), true);
    }

    public function orderBookV5($category = 'linear', $symbol = 'BTCUSDT', $limit = 60)
    {
        $endpoint="/v5/market/orderbook";
        $method="GET";
        $params="category=$category&symbol=$symbol&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol trades history"), true);
    }

    public function kline($symbol, $interval = '1m', $limit = 1000) //$interval - таймфрейм, $limit - свечи
    {
        //https://api-testnet.bybit.com/spot/v3/public/quote/kline?symbol=BTCUSDT&interval=1m&limit=1
        $endpoint="/spot/v3/public/quote/kline";
        $method="GET";
        $params="symbol=$symbol&interval=$interval&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol kline"), true);
    }

    public function klineV5($category = 'linear', $symbol, $interval = '30m', $limit = 1000) //$interval - таймфрейм, $limit - свечи
    {
        $intervalMap = [
            '15m' => '15',
            '30m' => '30',
            '1h' => '60',
            '4h' => '240',
            '1d' => 'D',
        ];

        $endpoint="/v5/market/kline";
        $method="GET";
        $params="category=$category&symbol=$symbol&interval=$intervalMap[$interval]&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol kline"), true);
    }

    public function derivativesInfo($symbol = '', $category = 'linear')
    {
        $endpoint="/derivatives/v3/public/instruments-info";
        $method="GET";
        $params= "category=$category";
        //$params= "symbol=$symbol&category=$category";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol derivatives Info"), true);
    }

    public function openInterest($symbol, $category = 'linear', $timeFrame = '30m', $limit = '2')
    {
        $endpoint="/v5/market/open-interest";
        $method="GET";

        if ($timeFrame == '30m')
            $timeFrame = '30min';
        else if ($timeFrame == '15m')
            $timeFrame = '15min';

        //GET /v5/market/open-interest?category=inverse&symbol=BTCUSD&intervalTime=5min&startTime=1669571100000&endTime=1669571400000 HTTP/1.1
        $params= "symbol=$symbol&category=$category&intervalTime=$timeFrame&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol Open Interest"), true);
    }

    public function lastOpenInterestByTimeframe($symbol, $category = 'linear', $timeFrame = '30min')
    {
        $endpoint="/v5/market/open-interest";
        $method="GET";
        //GET /v5/market/open-interest?category=inverse&symbol=BTCUSD&intervalTime=5min&startTime=1669571100000&endTime=1669571400000 HTTP/1.1

        if ($timeFrame == '30m')
            $timeFrame = '30min';

        $endTime = time() * 1000;
        $startTimeMap = [
          '30m' => $endTime - 1800000,
          '1h' => $endTime - 3600000,
          '4h' => $endTime - 14400000,
          '1d' => $endTime - 86400000,
        ];

        $params= "symbol=$symbol&category=$category&intervalTime=$timeFrame&startTimeMap=$startTimeMap[$timeFrame]&endTime=$endTime";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol Open Interest by timeframe map"), true);
    }

}
