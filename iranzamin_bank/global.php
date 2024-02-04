<?php

//define('PROXY', 'ctr-2-1m.geosurf.io:8000');
//define('PROXYUSERPWD', '630386+IR+630386-750244:e9fdbb701');
//define('COOKIE_FILE', "cookie.txt");
//define('COOKIE_FILE2', "cookie2.txt");

define('PROXY', 'pr.oxylabs.io:7777');
define('PROXYUSERPWD', 'customer-userrr3-cc-ir-sessid-1931399239-sesstime-30:amirrr000R3zA');
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

function getInputTag(string $html, string $pattern)
{
    $doc = new DOMDocument();
    preg_match($pattern, $html, $matches);
    if(!isset($matches[0]))
      return false;
      
    $text = "<html><body>
    $matches[0]
    </body></html>";
    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML($text);
    libxml_use_internal_errors($internalErrors);


    $result = null;
    $input = $doc->getElementsByTagName("input");
    if (isset($input[0]))
        $result = $input[0]->getAttribute("value");
    return $result;
}


function setPersianFormatForBalance(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    $encodedText = mb_convert_encoding(
        $text,
        'ISO-8859-1',
        'UTF-8');
    return trim(
        str_replace(
            $persianNumber,$englishNumber,$encodedText
        ));
}

function convertPersianNumberToEnglish(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    return str_replace($persianNumber, $englishNumber, $text);
}
