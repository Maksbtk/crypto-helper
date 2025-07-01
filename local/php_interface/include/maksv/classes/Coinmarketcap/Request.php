<?php
namespace Maksv\Coinmarketcap;

use Bitrix\Main\Data\Cache;

class Request
{
    protected string $api_key;
    protected string $url = 'https://pro-api.coinmarketcap.com';

    public function __construct(string $apiKey = '')
    {
        // Используем API-ключ из настроек, если не передан
        $this->api_key = $apiKey ?: \Maksv\Keys::COINMARKETCUP_API_KEY;
    }

    /**
     * Выполняет HTTP-запрос к CoinMarketCap API
     * @param string $endpoint
     * @param array  $parameters
     * @param bool   $useCache
     * @param int    $cacheTime (секунд)
     * @return string JSON-строка ответа
     */
    protected function httpReq(string $endpoint, array $parameters = [], bool $useCache = false, int $cacheTime = 120): string
    {
        $cache    = Cache::createInstance();
        $cacheID  = md5('cmc|' . $endpoint . '|' . implode('|', $parameters));
        $response = '';

        if ($useCache && $cache->initCache($cacheTime, $cacheID)) {
            $response = $cache->getVars();
            //echo '<pre>'; var_dump('cache y'); echo '</pre>';

        } elseif ($cache->startDataCache()) {
            $url     = $this->url . $endpoint . '?' . http_build_query($parameters);
            $headers = [
                'Accepts: application/json',
                'X-CMC_PRO_API_KEY: ' . $this->api_key,
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $cache->endDataCache($response);
        }

        return $response;
    }

    /**
     * Универсальный метод для получения исторических OHLCV данных через CMC
     * @param string      $symbol     Символ (например, 'TOTAL' или 'TOTAL2')
     * @param string      $interval   Интервал: '5m', '15m', 'hourly', 'daily'
     * @param int         $count      Количество точек (до 1000)
     * @param string|null $timeStart  ISO8601 или Unix timestamp
     * @param string|null $timeEnd    ISO8601 или Unix timestamp
     * @param string      $convert    Валюта конвертации ('USD' по умолчанию)
     * @param bool        $useCache   Использовать кэш?
     * @param int         $cacheTime  Время жизни кэша в секундах
     * @return array      Распарсенный ответ
     */
    public function ohlcvHistorical(
        string $symbol,
        string $interval,
        int $count = 200,
        ?string $timeStart = null,
        ?string $timeEnd = null,
        string $convert = 'USD',
        bool $useCache = true,
        int $cacheTime = 120
    ): array {
        $endpoint   = '/v1/cryptocurrency/ohlcv/historical';
        $params     = [
            'symbol'   => $symbol,
            'interval' => $interval,
            'count'    => $count,
            'convert'  => $convert,
        ];
        if ($timeStart) $params['time_start'] = $timeStart;
        if ($timeEnd)   $params['time_end']   = $timeEnd;

        $json = $this->httpReq($endpoint, $params, $useCache, $cacheTime);
        $data = json_decode($json, true);

        // Проверка на ошибки
        if (empty($data['data']['quotes']) || !is_array($data['data']['quotes'])) {
            throw new \RuntimeException('CMC ohlcv error: ' . ($data['status']['error_message'] ?? 'no data'));
        }

        // Преобразуем массив quotes в плоский список свечей
        $candles = array_map(function($quote) {
            return [
                't' => $quote['time_open'],
                'o'     => $quote['quote']['USD']['open'],
                'h'     => $quote['quote']['USD']['high'],
                'l'      => $quote['quote']['USD']['low'],
                'c'    => $quote['quote']['USD']['close'],
                'v'   => $quote['quote']['USD']['volume'],
            ];
        }, $data['data']['quotes']);

        return $candles;
    }

    /**
     * Получить массив последних 5-минутных свечей для Total Market Cap Excluding Top10
     * @param int  $count
     * @return array
     */
    public function getTotalExTop10_5m(int $count = 200, ): array
    {
        return $this->ohlcvHistorical('OTHERS', '5m', $count);
    }

    /**
     * Получить массив последних 15-минутных свечей для Total Market Cap Excluding Top10
     * @param int  $count
     * @return array
     */
    public function getTotalExTop10_15m(int $count = 200): array
    {
        return $this->ohlcvHistorical('TOTAL3', '15m', $count);
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
     * Получить рыночную капитализацию текущей цены актива в USD.
     *
     * Этот метод использует эндпоинт /v1/cryptocurrency/quotes/latest,
     * который доступен в бесплатном тарифе (до 10 000 запросов в месяц).
     *
     * @param string  $symbol     Символ актива (например, 'BTC' или 'ETH').
     * @param bool    $useCache   Использовать кэш (Bitrix Data Cache)?
     * @param int     $cacheTime  Время жизни кэша в секундах.
     * @return float   Market Cap в USD, или 0 при ошибке.
     * @throws \RuntimeException Если CMC вернул ошибку.
     */
    public function getMarketCap(string $symbol, bool $useCache = true, int $cacheTime = 3600): float
    {
        $endpoint   = '/v1/cryptocurrency/quotes/latest';
        $parameters = [
            'symbol'  => $symbol,
            'convert' => 'USD',
        ];

        $json = $this->httpReq($endpoint, $parameters, $useCache, $cacheTime);
        $data = json_decode($json, true);

        // Проверяем наличие ошибок
        if (empty($data['data'][$symbol]['quote']['USD']['market_cap'])) {
            $err = $data['status']['error_message'] ?? 'no market_cap field';
            throw new \RuntimeException("CMC getMarketCap error: $err");
        }

        return (float)$data['data'][$symbol]['quote']['USD']['market_cap'];
    }

    /**
     * Получить капитализации сразу по массиву символов или строке символов через запятую.
     *
     * @param string|array $symbols     Например 'BTC,ETH,XRP' или ['BTC','ETH','XRP']
     * @param bool   $useCache          Кешировать ли ответ?
     * @param int    $cacheTime         TTL кеша в секундах
     * @return array                    Ассоциативный массив ['BTC'=>cap, 'ETH'=>cap, …]
     * @throws \RuntimeException       При ошибке запроса
     */
    public function getMarketCaps($symbols, bool $useCache = true, int $cacheTime = 300): array
    {
        if (is_array($symbols)) {
            $symbols = implode(',', $symbols);
        }
        $endpoint   = '/v1/cryptocurrency/quotes/latest';
        $parameters = [
            'symbol'  => $symbols,
            'convert' => 'USD',
        ];

        $json = $this->httpReq($endpoint, $parameters, $useCache, $cacheTime);
        $data = json_decode($json, true);

        if (empty($data['data']) || !is_array($data['data'])) {
            $err = $data['status']['error_message'] ?? 'no data';
            throw new \RuntimeException("CMC getMarketCaps error: $err");
        }

        $result = [];
        foreach ($data['data'] as $sym => $info) {
            $result[$sym] = isset($info['quote']['USD']['market_cap'])
                ? (float)$info['quote']['USD']['market_cap']
                : 0.0;
        }
        return $result;
    }

}
