<?php
namespace Maksv\Binance;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;

class BinanceFutures
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
            $this->api_key = \Maksv\Keys::BINANCE_API_KEY;
            $this->secret_key = \Maksv\Keys::BINANCE_SECRET_KEY;
        }

        $this->url = "https://fapi.binance.com"; // Binance Futures API
    }

    public function openConnection()
    {
        $this->curl = curl_init();
    }

    public function closeConnection()
    {
        curl_close($this->curl);
    }

/*    public function httpReq($endpoint, $method = "GET", $params = [], $useCache = false, $cacheTime = 60)
    {
        $query = http_build_query($params);
        $fullUrl = $this->url . $endpoint . ($query ? '?' . $query : '');

        $cacheID = md5('httpReq|' . $endpoint . $method . $fullUrl );
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $res = [];

        if ($useCache && $cache->initCache($cacheTime, $cacheID)){
            $res = $cache->getVars();
        } elseif ($cache->startDataCache()) {

            curl_setopt_array($this->curl, [
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "X-MBX-APIKEY: $this->api_key",
                    "Content-Type: application/json"
                ],
            ]);

            if ($method === "POST") {
                curl_setopt($this->curl, CURLOPT_POST, true);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($params));
            }

            $response = curl_exec($this->curl);
            devlogs($response, 'dev');
            $res = json_decode($response, true);
            $cache->endDataCache($res);
        }

        return $res;
    }*/

    public function httpReq($endpoint, $method = "GET", $params = [], $useCache = false, $cacheTime = 60)
    {
        $query = http_build_query($params);
        $fullUrl = $this->url . $endpoint . ($query ? '?' . $query : '');

        $cacheID = md5('httpReq|' . $endpoint . $method . $fullUrl );
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $res = [];

        if ($useCache && $cache->initCache($cacheTime, $cacheID)){
            $res = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            curl_setopt_array($this->curl, [
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "X-MBX-APIKEY: $this->api_key",
                    "Content-Type: application/json"
                ],
            ]);

            if ($method === "POST") {
                curl_setopt($this->curl, CURLOPT_POST, true);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($params));
            }

            $response = curl_exec($this->curl);

            if (curl_errno($this->curl)) {
                $error = curl_error($this->curl);
                //devlogs("cURL error: $error", 'dev');

                $res = [
                    'error' => 'cURL error: ' . $error,
                    'code' => curl_errno($this->curl),
                ];
                $cache->abortDataCache(); // не кешируем ошибки
                return $res;
            }

            $decoded = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = json_last_error_msg();
                //devlogs("JSON decode error: $error", 'dev');

                $res = [
                    'error' => 'JSON decode error: ' . $error,
                    'raw' => $response,
                ];
                $cache->abortDataCache(); // не кешируем ошибки
                return $res;
            }

            $res = $decoded;
            $cache->endDataCache($res);
        }

        return $res;
    }

    public function getOpenInterestHist($symbol, $start = false, $end = false, $period = "5m", $limit = 300, $useCache = false, $cacheTime = 180)
    {
        $endpoint = "/futures/data/openInterestHist";
        $params = [
            "symbol" => $symbol,
            "period" => $period,
            "limit" => $limit,
            /*"startTime" => $start,
            "endTime" => $end,*/
        ];

        if ($start)
            $params['startTime'] = $start;

        if ($end)
            $params['endTime'] = $end;

        return $this->httpReq($endpoint, "GET", $params,  $useCache, $cacheTime);
    }

    public function getFuturesSymbols()
    {
        $endpoint = "/fapi/v1/exchangeInfo";
        $method = "GET";

        $response = $this->httpReq($endpoint, $method);

        $symbols = [];
        if (!empty($response['symbols']) && is_array($response['symbols'])) {
            foreach ($response['symbols'] as $symbolData) {
                if ($symbolData['contractType'] === 'PERPETUAL') { // Оставляем только перпетуальные контракты
                    $symbols[] = $symbolData;
                }
            }
        }

        return $symbols;
    }

    public function tradesHistory($symbol = 'BTCUSDT', $limit = 1000,  $useCache = false, $cacheTime = 60)
    {
        $endpoint = "/fapi/v1/trades";
        $method="GET";
        $params = [
            "symbol" => $symbol,
            "limit" => $limit,
        ];

        return $this->httpReq($endpoint, $method, $params,  $useCache, $cacheTime);
    }

    public function kline($symbol = 'BTCUSDT', $interval = '5m', $limit = 500, $start = false, $end = false,  $useCache = false, $cacheTime = 60)
    {
        $endpoint = "/fapi/v1/klines";
        $method="GET";
        $params = [
            "symbol" => $symbol,
            "limit" => $limit,
            "interval" => $interval,
        ];

        if ($start)
            $params['startTime'] = $start;

        if ($end)
            $params['endTime'] = $end;

        return $this->httpReq($endpoint, $method, $params,  $useCache, $cacheTime);
    }
}
