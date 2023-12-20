<?php 

require_once "./global.php";

define('USER_NAME','saman13680221');
define('PASSWORD','Amir@1362@NN');

$authenticateData = [
    'ajax' => true,
    'captcha' => $captchaCode = "",
    'client_id' => "pishkhan2",
    'item' => 1,
    'loading' => false,
    'mobile' => "",
    'nid' => USER_NAME,
    'otp' => "44163",//
    'password' => PASSWORD,
    'redirect_url' => "https://old.abplus.ir/auth",
    'response_type' => "code",
    'scope' => "openid",
    'second' => 0,
    'success' => false,
];

var_dump($authenticateData);

$ch = curl_init();
// for login
$authenticateUrl = "https://id.ba24.ir/core/authenticate";
curl_setopt($ch, CURLOPT_PROXY, PROXY);
// curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXYUSERPWD);
curl_setopt($ch, CURLOPT_URL, $authenticateUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    // "Accept: application/json", // application/x-www-form-urlencoded , text/plain, */*
    // "Content-type: application/x-www-form-urlencoded", 
    // "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    // "Accept-Encoding: gzip, deflate, br",
    // "Accept-Language: en-US,en;q=0.9,fa-IR;q=0.8,fa;q=0.7",
    // ":Authority:id.ba24.ir",
    // ":Method:POST",
    // ":Path:/core/authenticate",
    // ":Scheme:https",
    "Accept:*/*",// application/json, text/plain, ",
    // "Accept-Encoding:gzip, deflate, br",
    // "Accept-Language:en-US,en;q=0.9,fa-IR;q=0.8,fa;q=0.7",
    // "Cache-Control:no-cache",
    "Content-Length:0",
    "Content-Type:application/json", //;charset=UTF-8
    // "Content-Type:application/x-www-form-urlencoded", //;charset=UTF-8
    // "Origin:https://id.ba24.ir",
    // "Pragma:no-cache",
    // "Referer:https://id.ba24.ir/",
    // 'Sec-Ch-Ua:"Google Chrome";v="113", "Chromium";v="113", "Not-A.Brand";v="24"',
    // "Sec-Ch-Ua-Mobile:?0",
    // 'Sec-Ch-Ua-Platform:"Windows"',
    // "Sec-Fetch-Dest:empty",
    // "Sec-Fetch-Mode:cors",
    // "Sec-Fetch-Site:same-origin",
    "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    // "Content-Length: 0",
    "Connection: keep-alive",
    "Accept-Encoding: gzip, deflate, br",
]);
// curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($authenticateData));
$authenticateResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch);
var_dump($contentType);
writeOnFile('responses/authenticateResponse.html', $code .PHP_EOL. $contentType["content_type"] .PHP_EOL. $authenticateResponse);
if($code >= 400){
    echo "Login Failed !!! with Error code: $code";
    exit;
}

var_dump('END !!!');die;