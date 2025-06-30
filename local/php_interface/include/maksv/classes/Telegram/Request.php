<?php

namespace Maksv\Telegram;

header('Content-Type: text/html; charset=utf-8'); // на всякий случай досообщим PHP, что все в кодировке UTF-8

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Request
{

    protected string $botToken;
    protected array $inputData;
    protected string $chatId;

    public function __construct()
    {
        $this->botToken = \Maksv\Keys::TG_BOT_TOKEN;
    }

    public function messageToTelegram($text = 'test', $chatName = '@cryptoHelperAlerts', $photoPath = null)
    {
        devlogs($chatName . ' - ' . date("d.m.y H:i:s"), 'TelegramRequest');
        $chatId = self::chatIdByName($chatName);
        $token = $this->botToken;

        // Если передан массив с фото
        if (is_array($photoPath)) {
            $media = [];
            $files = [];

            foreach ($photoPath as $index => $path) {
                if (file_exists($path)) {
                    $fileKey = "photo" . $index; // Ключ для вложения
                    $files[$fileKey] = new \CURLFile($path); // Сохраняем файл в массив

                    $mediaItem = [
                        'type' => 'photo',
                        'media' => "attach://" . $fileKey // Ссылка на загруженный файл
                    ];

                    if ($index === 0) {
                        $mediaItem['caption'] = $text;
                        $mediaItem['parse_mode'] = 'HTML';
                    }

                    $media[] = $mediaItem;
                }
            }

            if (!empty($media)) {
                $url = "https://api.telegram.org/bot" . $token . "/sendMediaGroup";
                $params = [
                    'chat_id' => $chatId,
                    'media' => json_encode($media)
                ];

                $params = array_merge($params, $files); // Добавляем файлы в запрос
            } else {
                $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
                $params = [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ];
            }
        } // Если передана одна картинка
        elseif ($photoPath && file_exists($photoPath)) {
            $url = "https://api.telegram.org/bot" . $token . "/sendPhoto";
            $params = [
                'chat_id' => $chatId,
                'caption' => $text,
                'photo' => new \CURLFile($photoPath),
                'parse_mode' => 'HTML'
            ];
        } // Обычное сообщение
        else {
            $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
            $params = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        devlogs($result, 'TelegramRequest');

        return json_decode($result, true);
    }

    protected function chatIdByName($chatName)
    {
        switch ($chatName) {
            case '@cryptoHelperAlerts':
                $chatName = '-1002246605336';
                break;
            case '@infoCryptoHelperScreener':
                $chatName = '-1002194024408';
                break;
            case '@infoCryptoHelperTrend':
                $chatName = '-1002245853663';
                break;
            case '@infoCryptoHelperScreenerOkx':
                $chatName = '-1002148237966';
                break;
            /*case '@infoCryptoHelper1d':
                $chatName = '-1002236380560';
                break;*/
            case '@infoCryptoHelperDev':
                $chatName = '-1002236380560';
                break;
            case '@infoCryptoHelperScreenerBinance':
                $chatName = '-1002460854583';
                break;
            case '@cryptoHelperCornixTreadingBot':
                $chatName = '-1002639839004';
                break;
            case '@cryptoHelperProphetAi':
                $chatName = '-1002625670113';
                break;
            case '@cryptoHelperErrors':
                $chatName = '-1002195205048';
                break;
            /*default:
                $chatName = $chatName;
          */
        }

        return $chatName;
    }

}
