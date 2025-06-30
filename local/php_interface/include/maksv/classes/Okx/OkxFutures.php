<?php
namespace Maksv\Okx;

use Bitrix\Main\Data\Cache;

class OkxFutures
{
    protected string $api_key;
    protected string $secret_key;
    protected string $url;
    protected object $curl;

    public function __construct($apiKey = false, $secretKey = false)
    {
        if ($apiKey && $secretKey) {
            $this->api_key    = $apiKey;
            $this->secret_key = $secretKey;
        } else {
            $this->api_key    = \Maksv\Keys::OKX_API_KEY;
            $this->secret_key = \Maksv\Keys::OKX_SECRET_KEY;
        }

        // Базовый URL для REST API фьючерсов OKX
        $this->url = 'https://www.okx.com';
    }

    public function openConnection()
    {
        $this->curl = curl_init();
    }

    public function closeConnection()
    {
        curl_close($this->curl);
    }

    /**
     * Универсальный HTTP-запрос к OKX.
     *
     * @param string  $endpoint   Путь запроса (например, "/api/v5/market/history-candles")
     * @param string  $method     GET или POST
     * @param array   $params     Параметры запроса
     * @param bool    $useCache   Кешировать ли выдачу
     * @param int     $cacheTime  Время кеша в секундах
     * @return array
     */
    public function httpReq(string $endpoint, string $method = 'GET', array $params = [], bool $useCache = false, int $cacheTime = 60): array
    {
        $query = http_build_query($params);
        $fullUrl = $this->url . $endpoint . ($query ? '?' . $query : '');

        $cacheID = md5('OkxFutures|' . $method . '|' . $fullUrl);
        $cache   = Cache::createInstance();
        $res     = [];

        if ($useCache && $cache->initCache($cacheTime, $cacheID)) {
            $res = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            curl_setopt_array($this->curl, [
                CURLOPT_URL            => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "OK-ACCESS-KEY: {$this->api_key}",
                    // При необходимости добавить подпись и timestamp:
                    // "OK-ACCESS-SIGN: {$signature}",
                    // "OK-ACCESS-TIMESTAMP: {$timestamp}",
                    // "OK-ACCESS-PASSPHRASE: {$passphrase}",
                    "Content-Type: application/json",
                ],
            ]);

            if (strtoupper($method) === 'POST') {
                curl_setopt($this->curl, CURLOPT_POST, true);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($params));
            }

            $response = curl_exec($this->curl);

            if (curl_errno($this->curl)) {
                $res = [
                    'error' => 'cURL error: ' . curl_error($this->curl),
                    'code'  => curl_errno($this->curl),
                ];
                $cache->abortDataCache();
                return $res;
            }

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $res = [
                    'error' => 'JSON decode error: ' . json_last_error_msg(),
                    'raw'   => $response,
                ];
                $cache->abortDataCache();
                return $res;
            }

            $res = $decoded;
            $cache->endDataCache($res);
        }

        return $res;
    }

    /**
     * Исторические данные по открытому интересу.
     * OKX: /api/v5/public/open-interest?instId={symbol}&period={period}&limit={limit}
     */
    public function getOpenInterest(string $symbol, string $period = '5m', int $limit = 300, bool $useCache = false, int $cacheTime = 180): array
    {
        $endpoint = '/api/v5/public/open-interest';
        $params = [
            'instType' => 'SWAP',
            'instId' => $symbol,
            'period' => $period,
            'limit'  => $limit,
        ];
        return $this->httpReq($endpoint, 'GET', $params, $useCache, $cacheTime);
    }

    /**
     * История открытого интереса по контракту OKX.
     *
     * GET /api/v5/rubik/stat/contracts/open-interest-history
     *
     * @param string      $symbol    Инструмент, например 'BTC-USDT-SWAP'
     * @param int|false   $start     Начало периода в миллисекундах (timestamp * 1000) или false
     * @param int|false   $end       Конец периода в миллисекундах (timestamp * 1000) или false
     * @param string      $period    Интервал: '5m', '15m', '1h', '4h', '1d' и т.д.
     * @param int         $limit     Максимум точек (до 1440)
     * @param bool        $useCache  Флаг кеширования
     * @param int         $cacheTime Время кеша в секундах
     * @return array
     */
    public function getOpenInterestHist(
        string $symbol,
        $start = false,
        $end = false,
        string $period = '5m',
        int $limit = 300,
        bool $useCache = false,
        int $cacheTime = 180
    ): array {
        $endpoint = '/api/v5/rubik/stat/contracts/open-interest-history';
        $params = [
            'instId' => $symbol,
            'period' => $period,
            'limit'  => $limit,
        ];

        if ($start !== false) {
            // OKX ожидает миллисекунды
            $params['begin'] = (int)$start;
        }
        if ($end !== false) {
            $params['end'] = (int)$end;
        }

        return $this->httpReq($endpoint, 'GET', $params, $useCache, $cacheTime);
    }

    /**
     * Список доступных фьючерсных контрактов.
     * OKX: /api/v5/public/instruments?instType=SWAP (SWAP: Perpetual Futures)
     */
    public function getFuturesSymbols(bool $useCache = true, int $cacheTime = 300): array
    {
        $endpoint = '/api/v5/public/instruments';
        $params   = ['instType' => 'SWAP'];
        $response = $this->httpReq($endpoint, 'GET', $params, $useCache, $cacheTime);

        return $response['data'] ?? [];
    }

    /**
     * История торгов (ленту трейдов).
     * OKX: /api/v5/market/trades?instId={symbol}&limit={limit}
     */
    public function tradesHistory(string $symbol = 'BTC-USDT-SWAP', int $limit = 1000, bool $useCache = false, int $cacheTime = 60): array
    {
        $endpoint = '/api/v5/market/trades';
        $params   = [
            'instId' => $symbol,
            'limit'  => $limit,
        ];
        return $this->httpReq($endpoint, 'GET', $params, $useCache, $cacheTime);
    }

    /**
     * Свечи (клининги).
     * OKX: /api/v5/market/history-candles?instId={symbol}&bar={interval}&limit={limit}
     */
    /*public function kline(string $symbol = 'BTC-USDT-SWAP', string $interval = '5m', int $limit = 500, bool $useCache = false, int $cacheTime = 60): array
    {
        $intervalMap = [
            '5m' => '5m',
            '15m' => '15m',
            '30m' => '30m',
            '1H' => '1H',
            '4H' => '4H',
        ];
        $interval = $intervalMap[$interval];

        $endpoint = '/api/v5/market/history-candles';
        $params   = [
            'instId' => $symbol,
            'bar'    => $interval,
            'limit'  => $limit,
        ];
        return $this->httpReq($endpoint, 'GET', $params, $useCache, $cacheTime);
    }*/

    /**
     * getCandles — вернуть N последних k‑линий OKX (в том числе незакрытую) через /market/candles.
     *
     * @param string $symbol     Инструмент, например 'TRUMP-USDT-SWAP'
     * @param string $interval   Интервал: '1m','3m','5m','15m','30m','1H','2H','4H'
     * @param int    $limit      Сколько k‑линий нужно
     * @param bool   $useCache   Кешировать HTTP-запросы?
     * @param int    $cacheTime  TTL кеша (секунд)
     * @return array             Массив записей [ ts,o,h,l,c,vol,volCcy,volCcyQuote,confirm,'label'=>… ]
     */
    public function getCandles(
        string $symbol,
        string $interval,
        int    $limit,
        bool   $useCache  = false,
        int    $cacheTime = 120
    ): array {
        static $allowed = ['1m','3m','5m','15m','30m','1H','2H','4H'];
        if (!in_array($interval, $allowed, true)) {
            return ['err' => 'Unsupported interval'];
            //throw new \InvalidArgumentException("Unsupported interval “{$interval}”");
        }

        $endpoint   = '/api/v5/market/candles';
        $maxPerCall = 300;
        $fetched    = 0;
        $all        = [];
        $cursor     = null;  // будем пагинироваться «старше» через after

        while ($fetched < $limit) {
            // сколько берём в этом запросе
            $batchSize = min($limit - $fetched, $maxPerCall);

            // формируем параметры
            $params = [
                'instId' => $symbol,
                'bar'    => $interval,
                'limit'  => $batchSize,
            ];
            if ($cursor !== null) {
                // after=<ts> возвращает записи со ts < cursor
                $params['after'] = $cursor;
            }

            // запрос
            $resp = $this->httpReq($endpoint, 'GET', $params, $useCache, $cacheTime);
            $data = $resp['data'] ?? [];
            if (!is_array($data) || empty($data)) {
                // дальше нет записей
                break;
            }

            // кастим и добавляем метку
            $mapped = array_map(function(array $row) {
                // [ts,o,h,l,c,vol,volCcy,volCcyQuote,confirm]
                $row = array_map('floatval', $row);
                // метка для графика
                $dt = (new \DateTime('@' . floor($row[0] / 1000)))
                    ->setTimezone(new \DateTimeZone('UTC'));
                $row['label'] = $dt->format('H:i d.m');
                return $row;
            }, $data);

            // OKX может отдавать unsorted: упорядочим от новых к старым
            usort($mapped, fn($a, $b) => $b[0] <=> $a[0]);

            // мержим
            $all   = array_merge($all, $mapped);
            $count = count($mapped);
            $fetched += $count;

            // обновляем курсор — ts самой старой в пачке
            $cursor = end($mapped)[0];

            // если меньше, чем batchSize — дальше нет
            if ($count < $batchSize) {
                break;
            }
        }

        // убираем дубли по ts и обрезаем ровно $limit
        $uniq = [];
        foreach ($all as $row) {
            $uniq[$row[0]] = $row;
        }
        $out = array_values($uniq);

        $out = array_unique($out, SORT_REGULAR);
        usort($out, fn($a, $b) => $a[0] <=> $b[0]);
        return array_slice($out, 0, $limit);
    }


    /**
     * getCandlesHist — вернуть k‑линии OKX за промежуток (start, end).
     *
     * GET /api/v5/market/history-candles
     *
     * @param string    $symbol     Пример: 'TRUMP-USDT-SWAP'
     * @param string    $interval   '5m','15m','30m','1H','4H'
     * @param int|false $start      Нижняя граница (ms) или false
     * @param int|false $end        Верхняя граница (ms) или false
     * @param bool      $useCache   Кешировать запросы?
     * @param int       $cacheTime  TTL кеша (сек)
     * @return array                Массив [ts,o,h,l,c,vol,volCcy,volCcyQuote,confirm,'label'=>…]
     */
    public function getCandlesHist(
        string $symbol,
        string $interval,
               $start       = false,
               $end         = false,
        bool   $useCache  = false,
        int    $cacheTime = 60
    ): array {
        static $allowed = ['5m','15m','30m','1H','4H'];
        if (!in_array($interval, $allowed, true)) {
            throw new \InvalidArgumentException("Unsupported interval “{$interval}”");
        }

        $endpoint   = '/api/v5/market/history-candles';
        $maxPerCall = 300;
        $all        = [];

        // Если у нас есть оба конца, и start>=end — нет смысла
        if ($start !== false && $end !== false && $start >= $end) {
            return [];
        }

        // Устанавливаем курсор after = $end (или сейчас, если end не задан)
        $afterCursor = $end !== false
            ? (int)$end
            : (int)(microtime(true) * 1000);

        // Параметр before всегда равен start, если он есть
        $beforeParam = $start !== false
            ? (int)$start
            : null;

        while (true) {
            // 1) Сбор параметров
            $params = [
                'instId' => $symbol,
                'bar'    => $interval,
                'limit'  => $maxPerCall,
                'after'  => $afterCursor,
            ];
            if ($beforeParam !== null) {
                $params['before'] = $beforeParam;
            }

            // 2) Запрос
            $resp = $this->httpReq($endpoint, 'GET', $params, $useCache, $cacheTime);
            $data = $resp['data'] ?? [];
            if (!is_array($data) || empty($data)) {
                break;
            }

            // 3) Каст и label
            $mapped = array_map(function(array $row) {
                $row = array_map('floatval', $row);
                $dt  = (new \DateTime('@' . floor($row[0] / 1000)))
                    ->setTimezone(new \DateTimeZone('UTC'));
                $row['label'] = $dt->format('H:i d.m');
                return $row;
            }, $data);

            // 4) Сортировка от новых к старым, чтобы в начале был самый свежий
            usort($mapped, fn($a, $b) => $b[0] <=> $a[0]);

            // 5) Добавляем в результат
            $all = array_merge($all, $mapped);

            // 6) Обновляем afterCursor — берем самый старый ts (последний элемент в usort-отсортированном)
            $oldest = end($mapped)[0];
            $afterCursor = $oldest - 1;

            // 7) Если пришло меньше, чем maxPerCall — дальше нет
            if (count($mapped) < $maxPerCall) {
                break;
            }

            // 8) Если мы уже ушли в историю за start — можно остановиться
            if ($beforeParam !== null && $afterCursor <= $beforeParam) {
                break;
            }
        }

        // 9) Уникализируем по ts и фильтруем строго в (start, end)
        $uniq = [];
        foreach ($all as $row) {
            $ts = $row[0];
            // фильтр
            if ($start !== false && $ts <= $start) continue;
            if ($end   !== false && $ts >= $end)   continue;
            $uniq[$ts] = $row;
        }
        $out = array_values($uniq);

        // 10) Финальная сортировка от старых к новым
        usort($out, fn($a, $b) => $a[0] <=> $b[0]);

        return $out;
    }

}
