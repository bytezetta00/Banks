<?php 

require_once "./global.php";

define('USER_NAME','saman13680221');
define('PASSWORD','Amir@1362@NN');

$abplusUrl = "https://old.abplus.ir";
$authUrl = "https://id.ba24.ir/auth?response_type=code&scope=openid&client_id=pishkhan2&redirect_url=https://old.abplus.ir/auth";
$authUrl2 = "https://id.ba24.ir/auth/?response_type=code&scope=openid&client_id=pishkhan2&redirect_url=https://old.abplus.ir/auth";
$ba24Url = "https://id.ba24.ir/";
$meUrl = "https://id.ba24.ir/core/me";
$captchaUrl = "https://id.ba24.ir/core/inquiryCaptcha";

// if it's not logged in
$responseLogin = checkLogin();
var_dump(strlen($responseLogin["checkLoginResponse"]));
var_dump($responseLogin["code"]);
if($responseLogin["code"] == 200 ){
    // don't need to login
}
recreateNewFile(COOKIE_FILE);

$ch = curl_init();

curl_setopt($ch, CURLOPT_PROXY, PROXY);
curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXYUSERPWD);
curl_setopt($ch, CURLOPT_URL, $abplusUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json", 
    "Content-type: application/x-www-form-urlencoded",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
curl_setopt($ch, CURLOPT_VERBOSE, true);
$streamVerboseHandle = fopen('log.txt', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $streamVerboseHandle);
// rewind($streamVerboseHandle);
// $verboseLog = stream_get_contents($streamVerboseHandle);

// echo "cUrl verbose information:\n", 
//      "<pre>", htmlspecialchars($verboseLog), "</pre>\n";
// fwrite($streamVerboseHandle,$verboseLog);
// fclose($streamVerboseHandle);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$abplusResponse = curl_exec($ch);
writeOnFile('responses/abplusResponse.html', $code . $abplusResponse);
if($code >= 400 || $code == 0){
    echo "Server connection Error !!! Error code:$code";
    exit;
}

//die;

curl_setopt($ch, CURLOPT_URL, $authUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: */*", 
    "Content-type: application/x-www-form-urlencoded",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
]);
$authResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/authResponse.html', $code . $authResponse);


curl_setopt($ch, CURLOPT_URL, $authUrl2);
$authResponse2 = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/authResponse2.html', $code . $authResponse2);


curl_setopt($ch, CURLOPT_URL, $ba24Url);
$ba24Response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/ba24Response.html', $code . $ba24Response);


curl_setopt($ch, CURLOPT_URL, $meUrl);
$meResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/meResponse.html', $code . $meResponse);

// for getting captcha
curl_setopt($ch, CURLOPT_URL, $captchaUrl);
$captchaResponse = curl_exec($ch);
$captchaResponseObject = json_decode($captchaResponse);

// if we have captcha picture save it otherwise set it to false
$captchaData = 
            (is_object($captchaResponseObject) && $captchaResponseObject->hasCaptcha == true) ?
                $captchaResponseObject->captchaData : false;

$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/captchaResponse.html', $code . $captchaResponse);
if($code >= 400 || $code == 0){
    echo "Server connection Error !!! Error code:$code";
    exit;
}
var_dump(strlen($captchaData));
$captchaCode = "";
// if we have captcha in response 
if($captchaData){
    // save picture in a file for showing
    writeOnFile('images/captcha.svg',$captchaData);
    $captchaCode = readline('Enter the captcha:');
}

// for sending SMS
$otpUrl = "https://id.ba24.ir/core/sendOtp";
$otpData = [
    "captcha" => $captchaCode,
    "nid" => USER_NAME
];
curl_setopt($ch, CURLOPT_URL, $otpUrl);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($otpData));
$otpResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/sendOtp.html', $code . $otpResponse);
if($code >= 400 || $code == 0){
    echo "sending SMS failed!";
    exit;
}
echo $otpResponse.PHP_EOL;

$authenticateData = [
    'ajax' => true,
    'captcha' => $captchaCode,
    'client_id' => "pishkhan2",
    'item' => 1,
    'loading' => false,
    'mobile' => "",
    'nid' => USER_NAME,
    'otp' => "",//44163
    'password' => PASSWORD,
    'redirect_url' => "https://old.abplus.ir/auth",
    'response_type' => "code",
    'scope' => "openid",
    'second' => 0,
    'success' => false,
];

// getting SMS code from client
$authenticateData['otp'] = readline('Enter the SMS code:');
var_dump(json_encode($authenticateData));


// for login
$authenticateUrl = "https://id.ba24.ir/core/authenticate";
curl_setopt($ch, CURLOPT_URL, $authenticateUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept:*/*",
    "Content-Type:application/json",
    'TE' => 'trailers',
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($authenticateData));
$authenticateResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

writeOnFile('responses/authenticateResponse.html', $code .PHP_EOL. $contentType .PHP_EOL. $authenticateResponse);
if($code >= 400 || $code == 0){
    echo "Login Failed !!! with Error code: $code";
    exit;
}

$authOldCodeUrl = json_decode($authenticateResponse)->redirect_url ?? false;
if($authOldCodeUrl == false){
    echo "There is not the redirect url: $code .PHP_EOL. $contentType .PHP_EOL. $authenticateResponse";
    exit; 
}
var_dump($authOldCodeUrl); 

// curl_setopt($ch, CURLOPT_URL, $authUrl2); // "https://id.ba24.ir/auth/?response_type=code&scope=openid&client_id=pishkhan2&redirect_url=https://old.abplus.ir/auth";
// curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
// $authResponse3 = curl_exec($ch);
// $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// writeOnFile('responses/authResponse3.html', $code . $authResponse3);

// the getting redirect url after login 
curl_setopt($ch, CURLOPT_URL, $authOldCodeUrl); // "https://old.abplus.ir/auth?code=624eb9e8b0eb1ca0972db7de6c60ca6ba769fa9d5faaa5ff&state=undefined"
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: */*", 
    "Content-type: application/x-www-form-urlencoded",
    'TE' => 'trailers',
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
]);
$authOldCodeResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
if (curl_errno($ch)) {
    echo 'found Error:' . curl_error($ch);
}
writeOnFile('responses/authOldCodeResponse.html', $code . $contentType . $authOldCodeResponse);


// main page
// $dashboardUrl = "https://old.abplus.ir/dashboard";
// curl_setopt($ch, CURLOPT_URL, $dashboardUrl);
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     "Accept: */*", 
//     "Content-type: application/x-www-form-urlencoded",
//     'TE' => 'trailers',
//     "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
// ]);
// $dashboardResponse = curl_exec($ch);
// $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
// if (curl_errno($ch)) {
//     echo 'found Error:' . curl_error($ch);
// }
// var_dump($code);
// writeOnFile('responses/dashboardResponse.html', $code . $contentType . $dashboardResponse);

$csrfDashboardPattern = '/<input type="hidden" name="csrf" id="csrf" value=".*/';
$csrfDashboard = getInputTag($authOldCodeResponse , $csrfDashboardPattern);
var_dump(
    'csrfDashboard',
    $csrfDashboard
);
// for getting balance
curl_setopt($ch, CURLOPT_URL, "https://old.abplus.ir/panel/pishkhan/accountsStats?src=3");
curl_setopt($ch, CURLOPT_HEADER, [
    "Accept: */*", 
    "Content-type: application/json",
    'X-Requested-With' => 'XMLHttpRequest',
    'X-CSRF-TOKEN' => $csrfDashboard,
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
]);
$accountsStatsResponse3 = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/accountsStatsResponse3.html', $accountsStatsResponse3);

// select date and account page for deposits
$statementkarizUrl = "https://old.abplus.ir/panel/kariz/statementkariz";
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: */*", 
    "Content-type: application/json",
    'TE' => 'trailers',
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
]);
curl_setopt($ch, CURLOPT_URL, $statementkarizUrl);
$statementkarizResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
var_dump($code);
writeOnFile('responses/statementkarizResponse.html',  $statementkarizResponse);

$csrfPattern = '/<input type="hidden" name="csrf" id="csrf" value=".*">/';
$csrf = getInputTag($statementkarizResponse, $csrfPattern);

$startDatePattern = '/<input type="text" id="startDate" name="startDate" value=".*class=".*" data="0" readonly="1" autocomplete="off"/';
$startDate = getInputTag($statementkarizResponse , $startDatePattern);

$endDatePattern = '/<input type="text" id="endDate" name="endDate" value=".*class=".*" data="0" readonly="1" autocomplete="off"/';
$endDate = getInputTag($statementkarizResponse , $endDatePattern);


// for deposits
$statementkarizData = [
'fromAccount' =>	"0302672677006",
'startDate' =>	$startDate,
'endDate' =>	$endDate,
'filterStatementsKariz' =>	"",
'csrf' =>	$csrf,//eda0a6edbacbb35a89cf3d8cd51b5d64
];
var_dump($statementkarizData);

curl_setopt($ch, CURLOPT_URL, $statementkarizUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: */*", 
    "Content-type: application/x-www-form-urlencoded",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($statementkarizData));
$statementkarizPostResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
var_dump($code);
writeOnFile('responses/statementkarizPostResponse.html', $statementkarizPostResponse);
var_dump(getDeposit($statementkarizPostResponse)); //print deposits

curl_close($ch);

function checkLogin()
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_PROXY, PROXY);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXYUSERPWD);
    $dashboardUrl = "https://old.abplus.ir/dashboard";
    curl_setopt($ch, CURLOPT_URL, $dashboardUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Content-type: application/x-www-form-urlencoded",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    ]);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    $checkLoginResponse = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    writeOnFile('responses/checkLoginResponse.html', $code . $checkLoginResponse);
    return [
        "code" => $code,
        "checkLoginResponse" => $checkLoginResponse
    ];
}

function getInputTag(string $html, string $pattern)
{
    $doc = new DOMDocument();
    preg_match($pattern, $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
    $doc->loadHTML($text);

    $result = null;
    if ($doc->getElementsByTagName("input"))
        $result = $doc->getElementsByTagName("input")[0]->getAttribute("value");
    return $result;
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

    $result = [];
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