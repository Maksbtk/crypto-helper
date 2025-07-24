<?php
namespace Maksv\Bingx;

use Bitrix\Main\Data\Cache;

class BingxFutures
{
    protected string $api_key;
    protected string $secret_key;
    protected string $url;
    protected $curl;

    public function __construct($apiKey = null, $secretKey = null)
    {
        $this->api_key    = $apiKey    ?: \Maksv\Keys::BINGX_API_KEY;
        $this->secret_key = $secretKey ?: \Maksv\Keys::BINGX_SECRET_KEY;
        $this->url        = "https://open-api.bingx.com";
    }

    public function openConnection(): void
    {
        $this->curl = curl_init();
    }

    public function closeConnection(): void
    {
        curl_close($this->curl);
    }

    /**
     * Sign parameters with HMAC SHA256
     */
    protected function sign(array $params): string
    {
        ksort($params);
        $queryString = http_build_query($params);
        return hash_hmac('sha256', $queryString, $this->secret_key);
    }

    /**
     * Low‑level HTTP request helper
     */
    protected function httpReq(
        string $endpoint,
        string $method = 'GET',
        array  $params = [],
        bool   $signed = false,
        bool   $useCache = false,
        int    $cacheTime = 60
    ): array {
        $paramsToSend = $params;

        // add signature if needed
        if ($signed) {
            // add timestamp
            $paramsToSend['timestamp'] = round(microtime(true) * 1000);
            $paramsToSend['signature'] = $this->sign($paramsToSend);
        }

        $query = http_build_query($paramsToSend);
        $fullUrl = $this->url . $endpoint . ($query ? '?' . $query : '');

        $cacheID = md5("BingxHttp|{$method}|{$fullUrl}");
        $cache   = Cache::createInstance();
        $result  = [];

        if ($useCache && $cache->initCache($cacheTime, $cacheID)) {
            $result = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            curl_setopt_array($this->curl, [
                CURLOPT_URL            => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "X-BX-APIKEY: {$this->api_key}",
                    "Content-Type: application/json"
                ],
            ]);

            if (strtoupper($method) === 'POST') {
                curl_setopt($this->curl, CURLOPT_POST, true);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($params));
            }

            $response = curl_exec($this->curl);

            if (curl_errno($this->curl)) {
                $err = curl_error($this->curl);
                $cache->abortDataCache();
                return [
                    'error' => "cURL error: {$err}",
                    'code'  => curl_errno($this->curl),
                ];
            }

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $err = json_last_error_msg();
                $cache->abortDataCache();
                return [
                    'error' => "JSON decode error: {$err}",
                    'raw'   => $response,
                ];
            }

            $result = $decoded;
            $cache->endDataCache($result);
        }

        return $result;
    }

    /**
     * Get historical open interest
     *
     * @param string $symbol e.g. 'BTCUSDT'
     * @param string $period '1m','5m','1h', etc.
     * @param int|null $start startTime in ms
     * @param int|null $end endTime in ms
     * @param int $limit max data points (max 200)
     */
    public function getOpenInterestHist(
        string $symbol,
        string $period = '5m',
        int    $start  = null,
        int    $end    = null,
        int    $limit  = 200,
        bool   $useCache = false,
        int    $cacheTime = 300
    ): array {
        $endpoint = '/openApi/swap/v2/quote/openInterest';
        $params = [
            'symbol' => $symbol,
            'period' => $period,
            'limit'  => $limit,
        ];
        if ($start) $params['startTime'] = $start;
        if ($end)   $params['endTime']   = $end;

        return $this->httpReq($endpoint, 'GET', $params, false, $useCache, $cacheTime);
    }

    /**
     * Get kline/candlestick data
     *
     * @param string $symbol e.g. 'BTCUSDT'
     * @param string $interval '1m','5m','1h', etc.
     * @param int $limit number of points (max 500)
     * @param int|null $start startTime in ms
     * @param int|null $end endTime in ms
     */
    public function getKlines(
        string $symbol,
        string $interval = '5m',
        int    $limit    = 500,
        int    $start    = null,
        int    $end      = null,
        bool   $useCache = false,
        int    $cacheTime = 60
    ): array {
        $endpoint = '/openApi/swap/v3/quote/klines';
        $params = [
            'symbol' => $symbol,
            'interval' => $interval,  // в BingX это именно period
            'limit'  => $limit,
        ];
        if ($start) $params['startTime'] = $start;
        if ($end)   $params['endTime']   = $end;

        // сырое тело ответа
        $response = $this->httpReq($endpoint, 'GET', $params, false, $useCache, $cacheTime);

        // достаём массив свечек
        $data = $response['data'] ?? [];

        // приводим строки к float и сортируем
        $candles = [];
        foreach ($data as $candle) {
            $candles[] = [
                'open'   => (float) $candle['open'],
                'close'  => (float) $candle['close'],
                'high'   => (float) $candle['high'],
                'low'    => (float) $candle['low'],
                'volume' => (float) $candle['volume'],
                'time'   => (int)   $candle['time'],  // уже число
            ];
        }

        // сортировка по возрастанию времени
        usort($candles, function($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        return $candles;
    }

    /**
     * Fetch list of perpetual contracts
     */
    public function getFuturesContracts(): array
    {
        $endpoint = '/openApi/swap/v2/quote/contracts';
        return $this->httpReq($endpoint, 'GET');
    }

    /**
     * Fetch recent trades for a symbol
     */
    public function tradesHistory(
        string $symbol,
        int    $limit = 1000,
        bool   $useCache = false,
        int    $cacheTime = 60
    ): array {
        $endpoint = '/openApi/swap/v2/quote/trades';
        $params = ['symbol' => $symbol, 'limit' => $limit];
        return $this->httpReq($endpoint, 'GET', $params, false, $useCache, $cacheTime);
    }
}
