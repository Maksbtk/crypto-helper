<?php
namespace Maksv;

header('Content-Type: text/html; charset=utf-8'); // на всякий случай досообщим PHP, что все в кодировке UTF-8

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class TelegramBot
{

    protected string $botToken;
    protected array $inputData;
    protected string $chatId;

    public function __construct()
    {
        $this->botToken = \Maksv\Keys::TG_BOT_TOKEN;
    }

   public function messageToTelegram($text = 'test', $chatName = '@cryptoHelperDev')
   {
       devlogs($chatName, 'TelegramBot');
       $chatName = TelegramBot::chatIdByName($chatName);

       $token = $this->botToken;
       $url="https://api.telegram.org/bot".$token;
       $params = [
           'chat_id' => $chatName,
           'text' => $text
       ];

       $ch = curl_init($url . '/sendMessage');
       curl_setopt($ch, CURLOPT_HEADER, false);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       $result = curl_exec($ch);
       curl_close($ch);

       devlogs($chatName . ' - ' . date("d.m.y H:i:s"), 'TelegramBot');
       devlogs($result, 'TelegramBot');

       return  json_decode($result);
   }

   protected function chatIdByName($chatName) {
       switch ($chatName) {
           case '@cryptoHelperDev':
               $chatName = '-1002246605336';
               break;
           case '@infoCryptoHelper30m':
               $chatName = '-1002194024408';
               break;
           case '@infoCryptoHelper1h':
               $chatName = '-1002245853663';
               break;
           case '@infoCryptoHelper4h':
               $chatName = '-1002148237966';
               break;
           case '@infoCryptoHelper1d':
               $chatName = '-1002236380560';
               break;
           /*default:
               $chatName = $chatName;
         */
       }

       return $chatName;
   }

}
