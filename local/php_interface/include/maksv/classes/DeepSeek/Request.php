<?php
namespace Maksv\DeepSeek;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Data\Cache;

class Request {
    private $apiKey;
    private $baseUrl = 'https://api.deepseek.com/v1/';

    public function __construct($apiKey = false) {
        $this->apiKey = $apiKey ? $apiKey : \Maksv\Keys::DEEPSEEK_API_KEY;
    }

    public function sendRequest($endpoint, $method = 'GET', $data = []) {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            return $decodedResponse['error'];
            //throw new Exception('API Error: ' . ($decodedResponse['error'] ?? 'Unknown error'));
        }

        return $decodedResponse;
    }

    public function chatCompletion($messages, $model = 'deepseek-chat') {
        return $this->sendRequest('chat/completions', 'POST', [
            'model' => $model,
            'messages' => $messages
        ]);
    }
}
?>