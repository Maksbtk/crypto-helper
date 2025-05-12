<?php
namespace Maksv\Openapi;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Data\Cache;

class Request
{
    protected string $apiKey;
    protected string $endpointUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct(string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: \Maksv\Keys::OPENAI_API_KEY;
    }

    /**
     * Выполняет HTTP-запрос к OpenAI Chat API с опциональным кешированием
     *
     * @param array $body Параметры запроса (model, messages, temperature и т.д.)
     * @param bool  $useCache
     * @param int   $cacheTime
     * @return array Декодированный JSON-ответ
     * @throws \Exception
     */
    public function httpReq(array $body, bool $useCache = false, int $cacheTime = 120): array
    {
        $cache = Cache::createInstance();
        $cacheID = md5('openai|' . json_encode($body));

        if ($useCache && $cache->initCache($cacheTime, $cacheID)) {
            return $cache->getVars();
        }

        $ch = curl_init($this->endpointUrl);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => Json::encode($body),
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        // Прокси (если задан)
        if (!empty(\Maksv\Keys::HTTP_PROXY)) {
            $opts[CURLOPT_PROXY] = \Maksv\Keys::HTTP_PROXY;
            if (!empty(\Maksv\Keys::HTTP_PROXY_AUTH)) {
                $opts[CURLOPT_PROXYUSERPWD] = \Maksv\Keys::HTTP_PROXY_AUTH;
            }
        }

        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \Exception("cURL error: {$err}");
        }

        $data = Json::decode($resp);
        if (isset($data['error'])) {
            $msg = $data['error']['message'] ?? 'Unknown OpenAI error';
            throw new \Exception("OpenAI API error: {$msg}");
        }

        if (empty($data['choices'][0]['message']['content'])) {
            throw new \Exception('Empty response from OpenAI');
        }

        // Кешируем результат
        if ($useCache && $cache->startDataCache()) {
            $cache->endDataCache($data);
        }

        return $data;
    }

    /**
     * Формирует тело запроса: модель, сообщения (roles) и доп. параметры
     *
     * @param string   $model
     * @param array    $messages
     * @param float    $temperature
     * @return array
     */
    public function buildBody(string $model, array $messages, float $temperature = 0.7): array
    {
        return [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
        ];
    }
}