<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Bybit
{

    protected string $api_key;
    protected string $secret_key;
    protected string $url;
    protected object $curl;

    public function __construct(){

        /**
         * name - maksv crypto halper
         * API Key  - QOvYCttBiD4d9m7lNn
         * API Secret - qOBEvTvgFDthGFTRl97Vokq4lHo2KNW4wnLT
         * Permission - Contracts - Orders Positions  , USDC Contracts - Trade  , Unified Trading - Trade  , SPOT - Trade  , Wallet - Account Transfer Subaccount Transfer  , Exchange - Exchange History
         */

        $this->api_key='QOvYCttBiD4d9m7lNn'; # Input your API Key
        $this->secret_key='qOBEvTvgFDthGFTRl97Vokq4lHo2KNW4wnLT'; # Input your Secret Key
        //$this->url="https://api-testnet.bybit.com"; #Testnet environment
        $this->url="https://api.bybit.com";
        //$this->curl = curl_init();
    }
    
    public function openConnection()
    {
        $this->curl = curl_init();
    }

    public function closeConnection()
    {
        curl_close($this->curl);
    }

    public function httpReq($endpoint, $method, $params, $Info)
    {
        $res = [];
        $timestamp = time() * 1000;
        $params_for_signature= $timestamp . $this->api_key . "5000" . $params;
        $signature = hash_hmac('sha256', $params_for_signature, $this->secret_key);

        if($method=="GET")
            $endpoint=$endpoint . "?" . $params;

        curl_setopt_array($this->curl, array(
            CURLOPT_URL => $this->url . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => array(
                "X-BAPI-API-KEY: $this->api_key",
                "X-BAPI-SIGN: $signature",
                "X-BAPI-SIGN-TYPE: 2",
                "X-BAPI-TIMESTAMP: $timestamp",
                "X-BAPI-RECV-WINDOW: 5000",
                "Content-Type: application/json"
            ),
        ));

        if($method == "GET")
            curl_setopt($this->curl, CURLOPT_HTTPGET, true);

        $response = curl_exec($this->curl);

        /*$res = [
            'info' => $Info,
            'response' => $response,
            'lastHttpCode' => curl_getinfo($this->curl)['http_code'],
        ];*/
        $res = $response;

        return $res;
    }

    public function getSymbols()
    {
        $endpoint = "/spot/v3/public/symbols";
        $method = "GET";
        $params = "";
        return json_decode($this->httpReq($endpoint, $method, $params, "symbols"),true);
    }

    public function getSpotPrice($symbol)
    {
        $endpoint="/spot/v3/public/quote/ticker/price";
        $method="GET";
        $params="symbol=$symbol";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol price"), true);
    }

    public function getDepth($symbol, $limit = 10)
    {
        $endpoint="/spot/v3/public/quote/depth";
        $method="GET";
        $params="symbol=$symbol&limit=$limit";
        return json_decode($this->httpReq($endpoint, $method, $params, "$symbol price"), true);
    }

}
