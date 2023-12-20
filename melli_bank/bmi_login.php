<?php

require 'vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

define("PROXY", null);

$authReq = [
    "j_username" => "ha3078",
    "j_password" => "ha1365N@"
];

$res = curlPost(
    "https://my.bmi.ir/portalserver/j_spring_security_check",
    http_build_query($authReq),
    ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"]
);

$resObj = json_decode($res["body"]);
// var_dump($resObj);
if ($resObj != null) {
    $code = readline("Enter recieved code:");
    $verReq = $authReq + ["security_answer" => $code];
    // var_dump($verReq);

    $loginRes = curlPost(
        "https://my.bmi.ir/portalserver/j_spring_security_check",
        http_build_query($verReq),
        ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"],
        'https://my.bmi.ir/portalserver/home#/transaction'
    );

    // var_dump($loginRes);
    if ($loginRes["login"] == true) {
        $html = ($loginRes['body'] == false) ? getOfflineHtml() : $loginRes['body'];
        getAmount($html);
    } else {
        echo "Login Failed !!";
    }
} else {
    echo "Login Failed !!";
}
function curlPost($url, $data = NULL, $headers = [], $nextUrl = null, $proxy = PROXY, $userPass = null)
{
    // echo "geting data from URL:$url";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt"); // save cookie
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

    $response = curl_exec($ch);
    if (curl_error($ch)) {
        trigger_error('Curl Error:' . curl_error($ch));
    }
    if ($nextUrl != null && $response != null) {
        $response = json_decode($response);
        if ($response->success == true) {
            curl_setopt($ch, CURLOPT_URL, $nextUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
            $result = curl_exec($ch);
            curl_close($ch);
            return ["body" => $result, "login" => true]; //whole html
        }
    }
    curl_close($ch);
    return ["body" => $response, "headers" => $resHeaders, "login" => false];
}
function getOfflineHtml()
{
    echo "This amounts are your offline account balances: ";
    echo PHP_EOL;
    $myfile = fopen("melli.html", "r") or die("Unable to open file!");
    $melliHtml = fread($myfile, filesize("melli.html"));
    fclose($myfile);
    return $melliHtml;
}
function getAmount($html)
{
    $crawler = new Crawler($html);
    $countOfAccount = $crawler->filterXPath('//span[@class="account-balance pull-right hover-ability ng-isolate-scope ng-pristine ng-valid"]')->count();
    for ($count = 0; $countOfAccount > $count; $count++) {
        echo 'account of ' . $count + 1 . ': ';
        echo $crawler->filterXPath('//span[@class="account-balance pull-right hover-ability ng-isolate-scope ng-pristine ng-valid"]')->eq($count)->html() . PHP_EOL;
    }
}