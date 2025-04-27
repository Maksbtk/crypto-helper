<?php
namespace Maksv\Coinmarketcap;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Request
{

    protected string $api_key;
    protected string $url;

    public function __construct($apiKey = false, $secretKey = false)
    {
        $this->api_key = \Maksv\Keys::COINMARKETCUP_API_KEY;
    }

    public function httpReq($endpoint, $parameters, $useCache = false, $cacheTime = 120)
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheID = md5('httpReq|' . $endpoint . implode('|', $parameters));
        $res = [];
        if ($useCache && $cache->initCache($cacheTime, $cacheID)){
            $res = $cache->getVars();
        } elseif ($cache->startDataCache()) {

            $url = 'https://pro-api.coinmarketcap.com' . $endpoint;
            $headers = [
                'Accepts: application/json',
                'X-CMC_PRO_API_KEY: ' . $this->api_key
            ];

            $qs = http_build_query($parameters); // query string encode the parameters
            $request = "{$url}?{$qs}"; // create the request URL

            $curl = curl_init(); // Get cURL resource
            // Set cURL options
            curl_setopt_array($curl, array(
                CURLOPT_URL => $request,            // set the request URL
                CURLOPT_HTTPHEADER => $headers,     // set the headers
                CURLOPT_RETURNTRANSFER => 1         // ask for raw response instead of bool
            ));

            $response = curl_exec($curl); // Send the request, save the response
            curl_close($curl); // Close request

            $res = $response;
            $cache->endDataCache($res);
        }
        return $res; // print json decoded response
    }

    public function cryptocurrencyQuotesLatest($slug) {
        $endpoint = '/v1/cryptocurrency/quotes/latest';
        $parameters = [
            'slug' => $slug,//'bitcoin-dominance',
            'convert' => 'USDT',
        ];
        return json_decode($this->httpReq($endpoint, $parameters),true);
    }

    public function fearGreedLatest() {
        $endpoint = '/v3/fear-and-greed/latest';
        return json_decode($this->httpReq($endpoint, []),true);
    }

    /**
     * Универсальный метод для исторических OHLCV-данных
     *
     * @param string      $symbol     Символ (например, 'OTHERS' для Crypto Total Market Cap Excluding Top 10)
     * @param string      $interval   Интервал: '5m', '15m', 'hourly', 'daily' и т.д.
     * @param int         $count      Количество последних точек (например, 400)
     * @param string|null $timeStart  Начало периода в формате ISO 8601 или Unix-timestamp (опционально)
     * @param string|null $timeEnd    Конец периода в формате ISO 8601 или Unix-timestamp (опционально)
     * @param string      $convert    Валюта конвертации (по умолчанию 'USD')
     *
     * @return array      Распарсенный JSON-ответ с массивом свечей
     *
     * @see https://pro-api.coinmarketcap.com/v1/cryptocurrency/ohlcv/historical :contentReference[oaicite:0]{index=0}
     */
    public function ohlcvHistorical(
        string $symbol,
        string $interval,
        int $count,
        ?string $timeStart = null,
        ?string $timeEnd = null,
        string $convert = 'USDT',
        bool $useCache = false,
        int $cacheTime = 120,
    ): array {
        $endpoint = '/v1/cryptocurrency/ohlcv/historical';
        $parameters = [
            'symbol'   => $symbol,
            'interval' => $interval,
            'count'    => $count,
            'convert'  => $convert,
        ];
        if ($timeStart) {
            $parameters['time_start'] = $timeStart;
        }
        if ($timeEnd) {
            $parameters['time_end'] = $timeEnd;
        }
        return json_decode($this->httpReq($endpoint, $parameters, $useCache, $cacheTime), true);
    }

}
