<?php 

$rate = 385;
$sal = 1000*$rate;
$rent = 150000;
$billing = 15000;
$save = $sal * (0.3);
$cost = $sal - $rent - $billing - (int) $save;
var_dump(
    $cost
);
// this m
// t= +270 
// -49 ren
// -17 billing 
// = -66
// next m
// t= +385
// -25 for adding
// = -25
// ===== -41
// 15
// 200 velad + 182 de + 400 ren + 218 = 
require_once "./global.php";
$doc = new DOMDocument();
$file = fopen('responses/accountsStatsResponse.html','r');
$html = fread($file,5000000);
fclose($file);
$textForSMS = "رمز یکبار مصرف برای ورود به سامانه یکپارچه بانک آینده ( این رمز محرمانه است و میتوان از آن برای عبور در سامانه‌های بانک آینده استفاده کرد)
34786";
(strpos($textForSMS,'بانک آینده') !== false && strpos($textForSMS,'ورود') !== false);
preg_match_all('!\d{5}!', $textForSMS, $matches);
var_dump($matches[0][0]);
// $accountData = (is_object($html)) ? $html : (is_object(json_decode($html)) ? json_decode($html): false);
print_r(getBalance($html,"0302672677006"));die;
function getBalance(string $html, $accountNumber)
{
    $accountData = (is_object(json_decode($html))) ? json_decode($html): false ;
    $result = [];
    foreach($accountData->accounts as $index => $account){
        if($index == $accountNumber){
            $currentBalance = $account->currentBalance;
            $availableBalance = $account->availableBalance;
            $blockedAmount = $currentBalance - $availableBalance;
            $result = [
                'balance' => $availableBalance,
                'blocked_balance' => $blockedAmount,
            ];
        }
    }
    return $result;
}

die;

$csrfDashboardPattern = '/<input type="hidden" name="csrf" id="csrf" value=".*/';
$csrfDashboard = getInputTag($html , $doc, $csrfDashboardPattern);
var_dump(
    'csrfDashboard',
    $csrfDashboard
);die;
// $csrfPattern = '/<input type="hidden" name="csrf" id="csrf" value=".*">/';
// $csrf = getInputTag($html, $doc, $csrfPattern);
// var_dump($csrf);

// $startDatePattern = '/^<input type="text" id="startDate" name="startDate" value=".*="off">$/';
$startDatePattern = '/<input type="text" id="startDate" name="startDate" value=".*class=".*" data="0" readonly="1" autocomplete="off">/';
$startDate = getInputTag($html, $doc, $startDatePattern);
var_dump($startDate);

$endDatePattern = '/<input type="text" id="endDate" name="endDate" value=".*class=".*" data="0" readonly="1" autocomplete="off">/';
$endDate = getInputTag($html, $doc, $endDatePattern);
var_dump($endDate);die;

function getInputTag(string $html, DOMDocument $doc, string $pattern)
{
    preg_match($pattern, $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
    var_dump($text);
    $doc->loadHTML($text);

    $result = null;
    if ($doc->getElementsByTagName("input"))
        $result = $doc->getElementsByTagName("input")[0]->getAttribute("value");
    return $result;
}

die;
$authenticateData = [
    'ajax' => true,
    'captcha' => $captchaCode = "",
    'client_id' => "pishkhan2",
    'item' => 1,
    'loading' => false,
    'mobile' => "",
    'nid' => '11111',//USER_NAME,
    'otp' => "",//44163
    'password' => '11111111',//PASSWORD,
    'redirect_url' => "https://old.abplus.ir/auth",
    'response_type' => "code",
    'scope' => "openid",
    'second' => 0,
    'success' => false,
];

// getting SMS code from client
$authenticateData['otp'] = 33568;//readline('Enter the SMS code:');
var_dump($authenticateData);

$ch = curl_init();
// for login
$authenticateUrl = "https://id.ba24.ir/core/authenticate";
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
    "Accept:application/json, text/plain, */*",// text/plain, */*",
    // "Accept-Encoding:gzip, deflate, br",
    // "Accept-Language:en-US,en;q=0.9,fa-IR;q=0.8,fa;q=0.7",
    // "Cache-Control:no-cache",
    // "Content-Length:261",
    "Content-Type:text/plain", //;charset=UTF-8
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
]);
// curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($authenticateData));
$authenticateResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

writeOnFile('responses/authenticateResponse.html', $code .PHP_EOL. $contentType .PHP_EOL. $authenticateResponse);
if($code >= 400){
    echo "Login Failed !!! with Error code: $code";
    exit;
}

var_dump('END !!!');die;

$authOldCodeData = [
    'code' => '960fcb2d60925d798ac961f00d4dec5f6f0e6ccbb8de6e7a',
    'state' => 'undefined',
];
$authOldCodeUrl = "https://old.abplus.ir/auth?";//.http_build_query($authOldCodeData);

$ch = curl_init();
// curl_setopt($ch, CURLOPT_PROXY, PROXY);
// curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXYUSERPWD);
curl_setopt($ch, CURLOPT_URL, $authOldCodeUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Content-type: application/x-www-form-urlencoded"]);
curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
$abplusResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('error.html', $code . $abplusResponse);

die;
$balanceFile = fopen('neededHtml/balance.json', 'r');
$jsonBalance = fread($balanceFile, 10000);
fclose($balanceFile);
$data = json_decode($jsonBalance)->accounts;
// var_dump();

foreach ($data as $datum){
    var_dump("Current balance:",$datum->currentBalance);
    echo PHP_EOL;
    var_dump("Available balance:",$datum->availableBalance);
}

function getDeposit(string $html)
{
    $doc = new DOMDocument();
    preg_match('/<table class="table" id="table-result-resp">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    $text = str_replace($persianNumber,$englishNumber,$text);
    
    $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $trs = $doc->getElementsByTagName("tr");
    $enteghalPishkhanmajazi = "انتقال پيشخوان مجازي";
    $enteghalBeKart = "انتقال به كارت";

    
    // return $description1." - ".$description2;
    for ($i = 1; $trs->count() > $i; $i++) {
        $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
        if($deposit == ""){
            continue;
        }
        $datetime = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
        $bigintDatetime = str_replace(['/',':','-',' '],'',$datetime);
        $description1 = $trs->item($i)->getElementsByTagName("td")->item(5)->textContent;
        $description2 = $trs->item($i)->getElementsByTagName("td")->item(6)->textContent;
        $sharh = $trs->item($i)->getElementsByTagName("td")->item(7)->textContent;
        $serial = $bigintDatetime . "0000000";
        echo $description1;
        if($description1 == $enteghalPishkhanmajazi){
            $cardNumber = 'kiosk';
            $erja = $peygiri = $description2;
        }
        else if($description1 == $enteghalBeKart){
            preg_match_all('!\d{16}!', $description2, $matches);
            $cardNumber = $matches[0][0];
            preg_match_all('!\d{6}!', $sharh, $matches);
            $erja = $peygiri = $matches[0][0];
        }
        else{
            $cardNumber = "";
            $erja = $peygiri = "";
            continue;
        }
        $result[] = [
            'amount' => $deposit,
            'erja' => $erja,
            'peygiri' => $peygiri,
            'serial' => $serial,
            'card_number' => $cardNumber,
            'datetime' => $datetime,
            'bigint_datetime' => $bigintDatetime,
        ];
    }
    return $result;
}