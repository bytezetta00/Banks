<?php

define('PROXY', 'ctr-2-1m.geosurf.io:8000');
// define('PROXY', '46.209.54.110:8080');
define('PROXYUSERPWD', '630386+IR+630386-750244:e9fdbb701');
define('COOKIE_FILE', "cookie.txt");
define('COOKIE_FILE2', "cookie2.txt");

// function writeOnFile($filePath, $data, $mode = 'w')
// {
    // $captchaFile = fopen($filePath,$mode);
    // fwrite($captchaFile,$data);
    // fclose($captchaFile);
// }

function writeOnFile($filePath, $data, $mode = 'w')
{
    file_put_contents($filePath,$data);
}

function readCookieFile($text = "text : ",$len = 10000,$mode = 'r')
{
    $cookie = fopen(COOKIE_FILE,$mode);
    $scookie = fread($cookie ,$len);
    echo $text.$scookie.PHP_EOL;
    fclose($cookie);
}

function recreateNewFile($filePath)
{
    if (file_exists($filePath)) {
        unlink($filePath);
        $cookie = fopen($filePath, 'w');
        fwrite($cookie , '');
        fclose($cookie);
    }
}