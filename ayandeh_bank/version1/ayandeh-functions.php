<?php
function curlRequest(string $url, $headers = [], $data = NULL, $proxy = PROXY, $proxyuserpwd = PROXYUSERPWD, $cookieFile = COOKIE_FILE, $userPass = null)
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

function getCaptchaCode($captchaResponse,$captchaFile)
{
    $captchaResponseObject = json_decode($captchaResponse);
    // if we have captcha picture save it otherwise set it to false
    $captchaData =
        (is_object($captchaResponseObject) && $captchaResponseObject->hasCaptcha == true) ?
        $captchaResponseObject->captchaData : false;


    $captchaCode = "";
    // if we have captcha in response 
    if ($captchaData) {
        // save picture in a file for showing
        writeOnFile('images/captcha.svg', $captchaData);
        // $captchaCode = readline('Enter the captcha:');
        load('captcha-api');
        $api = new CaptchaAPI();
        $captchaCode = $api->solve($captchaFile);
    }
    return $captchaCode;
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

function getAuthenticateData($captchaCode, $userName = USER_NAME, $password = PASSWORD): array
{
    return [
        'ajax' => true,
        'captcha' => $captchaCode,
        'client_id' => "pishkhan2",
        'item' => 1,
        'loading' => false,
        'mobile' => "",
        'nid' => $userName,
        'otp' => "",//44163
        'password' => $password,
        'redirect_url' => "https://old.abplus.ir/auth",
        'response_type' => "code",
        'scope' => "openid",
        'second' => 0,
        'success' => false,
    ];
}

function getStatementkarizData(string $statementkarizResponse,$accountNumber = ACCOUNT_NUMBER): array
{
    $csrfPattern = '/<input type="hidden" name="csrf" id="csrf" value=".*">/';
    $csrf = getInputTag($statementkarizResponse, $csrfPattern);
    $startDatePattern = '/<input type="text" id="startDate" name="startDate" value=".*class=".*" data="0" readonly="1" autocomplete="off"/U';
    $startDate = getInputTag($statementkarizResponse, $startDatePattern);
    $endDatePattern = '/<input type="text" id="endDate" name="endDate" value=".*class=".*" data="0" readonly="1" autocomplete="off"/U';
    $endDate = getInputTag($statementkarizResponse, $endDatePattern);

    return [
        'fromAccount' => $accountNumber,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'filterStatementsKariz' => "",
        'csrf' => $csrf, //eda0a6edbacbb35a89cf3d8cd51b5d64
    ];
}

function getDeposit(string $html, $userId, $bankingId): array
{
    $doc = new DOMDocument();
    preg_match('/<table class="table" id="table-result-resp">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    $text = str_replace($persianNumber, $englishNumber, $text);

    $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $trs = $doc->getElementsByTagName("tr");
    $enteghalPishkhanmajazi = "انتقال پيشخوان مجازي";
    $enteghalBeKart = "انتقال به كارت";

    $result = [];
    // return $description1." - ".$description2;
    for ($i = 1; $trs->count() > $i; $i++) {
        $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
        if ($deposit == "") {
            continue;
        }
        $datetime = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
        $bigintDatetime = str_replace(['/', ':', '-', ' '], '', $datetime);
        $description1 = $trs->item($i)->getElementsByTagName("td")->item(5)->textContent;
        $description2 = $trs->item($i)->getElementsByTagName("td")->item(6)->textContent;
        $sharh = $trs->item($i)->getElementsByTagName("td")->item(7)->textContent;
        $serial = $bigintDatetime . "0000000";

        if ($description1 == $enteghalPishkhanmajazi) {
            $cardNumber = 'kiosk';
            $erja = $peygiri = $description2;
        } else if ($description1 == $enteghalBeKart) {
            preg_match_all('!\d{16}!', $description2, $matches);
            $cardNumber = $matches[0][0];
            preg_match_all('!\d{6}!', $sharh, $matches);
            $erja = $peygiri = $matches[0][0];
        } else {
            $cardNumber = "";
            $erja = $peygiri = "";
            continue;
        }
        $result[] = [
            'user_id' => $userId,
            'banking_id' => $bankingId,
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

function getUrls(): array
{
    return [
        "abplus" => "https://old.abplus.ir",
        "auth" => "https://id.ba24.ir/auth?response_type=code&scope=openid&client_id=pishkhan2&redirect_url=https://old.abplus.ir/auth",
        "auth2" => "https://id.ba24.ir/auth/?response_type=code&scope=openid&client_id=pishkhan2&redirect_url=https://old.abplus.ir/auth",
        "ba24" => "https://id.ba24.ir/",
        "me" => "https://id.ba24.ir/core/me",
        "captcha" => "https://id.ba24.ir/core/inquiryCaptcha",
        "otp" => "https://id.ba24.ir/core/sendOtp",
        "authenticate" => "https://id.ba24.ir/core/authenticate",
        "accountsStats" => "https://old.abplus.ir/panel/pishkhan/accountsStats?src=3",
        "statementkariz" => "https://old.abplus.ir/panel/kariz/statementkariz",
    ];
}

function getBalance(string $html, $accountNumber = ACCOUNT_NUMBER)
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

function writeOnFile($filePath, $data, $mode = 'w')
{
    file_put_contents($filePath,$data);
}