<?php

// define('PROXY', '46.209.54.110:8080');
define('PROXY', 'pr.oxylabs.io:7777');
define('PROXYUSERPWD', 'customer-operators-cc-ir-sessid-0931399234-sesstime-30:WHqEsCVG269ism');
define('COOKIE_FILE', "cookie.txt");
define('COOKIE_FILE2', "cookie2.txt");

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