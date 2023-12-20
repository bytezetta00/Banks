<?php

require_once "./DevCoder/DotEnv.php";
// require_once "./index.php";
require_once "../global.php";
require_once "./Data.php";
require_once "./Login.php";
use DevCoder\DotEnv;

(new DotEnv(__DIR__ . '/.env'))->load();

// echo getenv('APP_NAME').PHP_EOL;
// echo getenv('USERNAME').PHP_EOL;
// echo getenv('PASSWORD').PHP_EOL;


$domDocument = new DOMDocument;
$data = new Data;
$login = new Login(
    $domDocument,
    $data
);

$firstLoginCurl = $login->firstLoginCurl();
$secondLoginCurl = $login->secondLoginCurl($firstLoginCurl['ch']);
$thirdLoginCurl = $login->thirdLoginCurl($secondLoginCurl['ch']);
$data->loginData['loginToken'] = $data->getInputTag($thirdLoginCurl['thirdResponse'], $domDocument, '/<input type="hidden" name="loginToken" value=".*/'); //get current token
$getCaptchaCurl = $login->getCaptchaCurl($thirdLoginCurl['ch']);
if ($getCaptchaCurl['captchaRawImage'] != '') {
    writeOnFile('../version2/images/captcha.png', $getCaptchaCurl['captchaRawImage']);
}

// var_dump(
//     $thirdLoginCurl
// );

