<?php
namespace Maksv\MachineLearning;

class Request
{
    private string $trainUrl;
    private string $predictUrl;
    private string $predictBatchUrl;

    /**
     * @param string $host Хост (домен или IP) с указанием порта, например 'http://127.0.0.1:8000'
     */
    public function __construct(string $host = 'http://127.0.0.1:8000', string $dir = 'ml' )
    {
        //  можно поставить 'https://infocrypto-helper.ru:8000'
        $this->trainUrl   = rtrim($host, '/') . '/'.$dir.'/train';
        $this->trainFileUrl   = rtrim($host, '/') . '/'.$dir.'/train-file';
        $this->predictUrl = rtrim($host, '/') . '/'.$dir.'/predict';
        $this->predictBatchUrl = rtrim($host, '/') . '/'.$dir.'/predict-batch';
    }

    /**
     * @param array $signals Массив объектов TrainSignal в формате:
     *  [
     *    ['candles'=>[ ['o'=>..,'h'=>..,'l'=>..,'c'=>..,'v'=>..], ... ],
     *     'entry'=>..., 'tp1'=>..., 'tp2'=>..., 'sl'=>..., 'future'=>[ ['h'=>..,'l'=>..], ... ]
     *    ],
     *    ...
     *  ]
     * @return array Ответ сервиса, например ['status'=>'trained','n_samples'=>123]
     */
    public function train(array $signals): array
    {
        return $this->request($this->trainUrl, $signals);
    }

    public function trainFile(string $trainFilePath): array
    {
        return $this->request($this->trainFileUrl,  ['trainFilePath' => $trainFilePath]);
    }

    /**
     * @param array $signal Одиночный PredictSignal:
     *  ['candles'=>[ ... ], 'entry'=>.., 'tp1'=>.., 'tp2'=>.., 'sl'=>..]
     * @return array Ответ сервиса, например ['status'=>'ok','probabilities'=>[0.1,0.8,0.1]]
     */
    public function predict(array $signal): array
    {
        return $this->request($this->predictUrl, $signal);
    }

    /**
     * @param array $signal пакет Signal:
     * @return array Ответ сервиса []
     */
    public function predictBatch(array $signals): array
    {
        return $this->request($this->predictBatchUrl, $signals);
    }

    /**
     * Выполнить HTTP POST и вернуть распарсенный JSON
     * @param string $url
     * @param mixed  $data
     * @return array
     * @throws \Exception
     */
    private function request(string $url, $data): array
    {
        $ch = curl_init($url);
        $payload = json_encode($data);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
            CURLOPT_CONNECTTIMEOUT  => 5,    // сколько секунд ждать установку соединения
            CURLOPT_TIMEOUT         => 60,   // сколько секунд ждать полного ответа
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \Exception("ML request failed: $err");
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode($resp, true);
        if ($code >= 400) {
            $msg = $json['error'] ?? $resp;
            throw new \Exception("ML service error [$code]: $msg");
        }
        return $json;
    }
}
