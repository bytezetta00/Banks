<?php

class Price
{

    public function __construct(){

    }

    public function getPrices()
    {
        $coins = $this->curlRequest('https://api.kucoin.com/api/v1/prices?base=USD&currencies=')['body'];
//        $result = count(json_decode($coins)->data);
        $data = json_decode($coins,true)['data'];
        foreach ($data as $index => $datum){
            $result[$index] = $datum;
        }
        return $result;
    }

    public function getAllInformation($symbol = null)
    {
        $coins = $this->curlRequest('https://api.kucoin.com/api/v1/market/allTickers')['body'];
        $data = json_decode($coins,true)['data']['ticker'];
        foreach ($data as $index => $datum){
            if(!$symbol) {
                $result[$index] = $datum;
            }elseif($datum["symbol"] == $symbol){
                $result[$index] = $datum;
            }
        }
        return $result;
    }
    public function curlRequest(string $url, $data = NULL, $headers = [], $proxy = null, $proxyuserpwd = null, $cookieFile = null, $userPass = null)
    {
        // echo "geting data from URL:$url";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        // curl_setopt($ch, CURLOPT_ENCODING, 'identity');

        $resHeaders = [];
        // this function is called by curl for each header received
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$resHeaders) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $resHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );


        if ($userPass) {
            curl_setopt($ch, CURLOPT_USERPWD, $userPass);
        }

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        if (!empty($proxyuserpwd)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuserpwd);
        }

        $response = curl_exec($ch);
        if (curl_error($ch)) {
            trigger_error('Curl Error:' . curl_error($ch));
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            "body" => $response,
            "headers" => $resHeaders,
            "code" => $code
        ];
    }

}

var_dump((new Price())->getAllInformation('SOL-USDT'));
