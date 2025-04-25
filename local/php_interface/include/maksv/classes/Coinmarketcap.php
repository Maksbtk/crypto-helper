<?php
namespace Maksv;

use Bitrix\Main\Loader,
    Bitrix\Main\Data\Cache;


class Coinmarketcap
{

    protected string $api_key;
    protected string $url;

    public function __construct($apiKey = false, $secretKey = false)
    {
        $this->api_key = \Maksv\Keys::COINMARKETCUP_API_KEY;
    }

    public function httpReq($endpoint, $parameters)
    {
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

        return $response; // print json decoded response
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

}
