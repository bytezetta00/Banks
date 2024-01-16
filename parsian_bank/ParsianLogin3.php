<?php

require_once "./global.php";

class ParsianLogin
{
    protected DOMDocument $domDocument;
    protected array $loginData;
    protected array $loginData2;
    public function __construct(
        private string $userName = "0019209053",//"6539486431",//"2741558191",//"0010517881",
        //"meysam8900",
        private string $password = "Hedie@1375",//"Amir@1362m@Ss",//"D@nyal0118DGk",//"M10510568m@Kk",
        //"SH@nyal0118DG",
        private string $account = "47001499225602",//"47001485069601",//"30101927557601",//"30101790267603",
        //"47001427876609",
        private string $proxy = PROXY,
        private string $proxyUserPwd = PROXYUSERPWD,
    ) {
        $this->domDocument = new DOMDocument();
        $this->loginData = $this->getLoginData();
        $this->loginData2 = $this->getLoginData2();
    }

    public function login()
    {
        $date = date('Y-m-d h:i:s.000', time());
        $formattedDate = str_replace(' ','T',$date."Z");

        $previousDate = date('Y-m-d h:i:s.000', strtotime($date . ' -1 months'));
        $formattedPreviousDate = str_replace(' ','T',$previousDate."Z");

        $loginHtmlUrl = "https://ipb.parsian-bank.ir/login.html";
        $loginHtmlResponse = $this->curlRequest($loginHtmlUrl);
        writeOnFile('responses/loginHtmlResponse.html', $loginHtmlResponse["body"]);

//        $vendorsVersionUrl = "https://ipb.parsian-bank.ir/vendors/version";
//        $vendorsVersionResponse = $this->curlRequest($vendorsVersionUrl);
//        writeOnFile("responses/vendorsVersionResponse.html", $vendorsVersionResponse["body"]);

        $forLoginUrl = "https://ipb.parsian-bank.ir/vendors/captcha/forLogin";
        $forLoginResponse = $this->curlRequest($forLoginUrl);
        writeOnFile('images/captcha.png', $forLoginResponse["body"]);

        //POST
        $loginUrl = "https://ipb.parsian-bank.ir/login";
        $this->loginData['captcha'] = readline('Enter the captcha:');

//        challengeKey=&langKey=fa&browserMode=public&otpInProgress=false&currentStep=1&pib_username=6539486431&pib_password=Amir%401362m%40Ss&passwordType=S&captcha=h88ee
        $loginResponse = $this->curlRequest($loginUrl,$this->loginData);
        writeOnFile("responses/loginResponse.html", $loginResponse["body"]);
         if ($loginResponse['body'] == 1002) {
             echo "captcha wrong!";//log
             return false;
         }
//        POST
//	https://ipb.parsian-bank.ir/login

//        challengeKey=cd2a6958-b9ff-45df-af7e-1e16629&langKey=fa&browserMode=public&otpInProgress=true&currentStep=2&pib_username=6539486431&pib_password=Amir%401362m%40Ss&passwordType=S&otpPassword=15720&captcha=h88ee

        $this->loginData2['challengeKey'] = $loginResponse["body"];
        $this->loginData2['otpPassword'] = readline('Enter the SMS:');

        var_dump($this->loginData2);

        $login2Response = $this->curlRequest($loginUrl,$this->loginData2);
        writeOnFile("responses/login2Response.html", $login2Response["body"]);
        if ($login2Response['body'] == 1003) {
            echo "sms code wrong!";//log
            return false;
        }
        //home page url
        $homeUrl = "https://ipb.parsian-bank.ir/";
        $homeResponse = $this->curlRequest($homeUrl);
        writeOnFile('responses/homeResponse.html', $homeResponse["body"]);

        $getAllAccountsUrl = "https://ipb.parsian-bank.ir/account/getAllAccounts";
        $getAllAccountsData = [
            "currency" => "IRR"
        ];
        $getAllAccountsResponse = $this->curlRequest($getAllAccountsUrl,json_encode($getAllAccountsData),[
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ]);
        writeOnFile('responses/getAllAccountsResponse.html', $getAllAccountsResponse["body"]);
        $balance = $this->getBalance($getAllAccountsResponse['body'],$this->account);
        var_dump($balance);

        $statementUrl = "https://ipb.parsian-bank.ir/account/statement";
        $statementData = [
            "accountNumber" => $this->account,
            "fromDate" => $formattedPreviousDate,//"2023-12-19T20:00:00.000Z",// 1 month before
            "toDate" => $formattedDate,//"2024-01-06T09:29:13.896Z", // current month
        ];
        $statementResponse = $this->curlRequest($statementUrl,json_encode($statementData),[
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ]);
        writeOnFile('responses/statementResponse.html', $statementResponse["body"]);
        $statement = $this->getDeposits($statementResponse["body"], $user_id = 1, $banking_id = 1);
        var_dump($statement);
//        {"accountNumber":"47001499225602","orderType":2,"fromDate":1704486600565,"length":null}
        //{"accountNumber":"47001499225602","fromDate":"2023-12-21T20:00:00.000Z","toDate":"2024-01-06T09:29:13.896Z"}
//        {"totalRecord":0,"accountNumber":"47001499225602","rowDtoList":[]}


        //cd2a6958-b9ff-45df-af7e-1e16629
        return true;

    }

    public function curlRequest(string $url, $data = NULL, $headers = [], $proxy = PROXY, $proxyuserpwd = PROXYUSERPWD, $cookieFile = COOKIE_FILE, $userPass = null)
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

    public function getLoginData()
    {
        return [
            "challengeKey" => "",
            "langKey" => "fa",
            "browserMode" => "public",
            "otpInProgress" => "false",
            "currentStep" => "1",
            "pib_username" => $this->userName,
            "pib_password" => $this->password,
            "passwordType" => "S",
        ];
    }

    public function getLoginData2()
    {
        return [
            "challengeKey" => "",
            //"7204495e-2db0-41a9-9579-d2ae4d6",
            "langKey" => "fa",
            "browserMode" => "public",
            "otpInProgress" => "true",
            "currentStep" => "2",
            "pib_username" => $this->userName,
            "pib_password" => $this->password,
            "passwordType" => "S",
            //"otpPassword" => "",
            "captcha" => "bbxhc",
        ];
    }

//    public function makeid(int $length) {
//        $result = '';
//        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
//        $charactersLength = strlen($characters);
//        for ($i = 0; $i < $length; $i++) {
//            $result .= $characters[rand(0, $charactersLength-1)];
//        }
//        return $result;
//    }
    public function getBalance(string $html,$accountNumber)
    {
        if($html != null && $html != "")
        {
            $getAllAccountsJson = json_decode($html);
            if((json_last_error() === JSON_ERROR_NONE))
            {
                $accounts = $getAllAccountsJson->allAccountList;
                $balance = [];
                foreach ($accounts as $account){
                    if($account->depositNumber == $accountNumber){
                        $balance['balance'] = (int) $account->balance;
                        $balance['blockedAmount'] = (int) $account->balance - (int) $account->availableBalance;
                    }
                }
                return $balance ?? false;
            }
            else{
                var_dump("Invalid Json In Balance.");
                return false;
            }
        }else{
            var_dump("There is an empty response !");
            return false;
        }
    }
    public function getDeposits(string $html, $user_id, $banking_id)
    {
        $result = json_decode($html);
        $statement = [];
        if ((json_last_error() === JSON_ERROR_NONE)) {
            if ($result == null || $result == "")
                return false;

            if (!key_exists('totalRecord', (array)$result))
                return false;

            for ($i = 0; $i < $result?->totalRecord; $i++) {
                $row = $result?->rowDtoList[$i];
                if ($row === null || $row === "")
                    return false;

                $amount = $row?->transferAmount;
                if ($amount > 0) {
                    $date = $row?->date;
                    $description = $row?->description;
                    $serial = $row?->referenceNumber;
                    if($serial == '' || $serial == null){
                        continue;
                    }
                    if (str_contains($description, 'تراکنش پُل')) {
                        preg_match('!کدِ رهگیریِ \d{20,22}!', $description, $firstTrackingNumberMatches);
                        if(!key_exists(0,$firstTrackingNumberMatches)){
                            continue;
                        }
                        preg_match('!\d{20,22}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
                        if(!key_exists(0,$secondTrackingNumberMatches)){
                            continue;
                        }
                        preg_match('!IR\d{24}!', $description, $shebaMatches);
                        if(!key_exists(0,$shebaMatches)){
                            continue;
                        }
                        $shebaNumber = $shebaMatches[0];
                        $trackingNumber = $secondTrackingNumberMatches[0];
                        $peygiri = $erja = $trackingNumber;
                        $cardNumber = $shebaNumber;
                    }
                    if (str_contains($description, 'انتقال از کارت')) {
                        preg_match('!از کارت \d{16}!', $description, $firstCardNumberMatches);
                        if(!key_exists(0,$firstCardNumberMatches)){
                            continue;
                        }
                        preg_match('!\d{16}!', $firstCardNumberMatches[0], $secondCardNumberMatches);
                        if(!key_exists(0,$secondCardNumberMatches)){
                            continue;
                        }
                        preg_match('!شماره پیگیری \d{5,7}!', $description, $firstTrackingNumberMatches);
                        if(!key_exists(0,$firstTrackingNumberMatches)){
                            continue;
                        }
                        preg_match('!\d{5,7}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
                        if(!key_exists(0,$secondTrackingNumberMatches)){
                            continue;
                        }

                        $peygiri = $erja = $secondTrackingNumberMatches[0];
                        $cardNumber = $secondCardNumberMatches[0];
                    }
                    if (str_contains($description, 'انتقالی حساب')) {
                        preg_match('!\d{12,14}!', $description, $accountMatches);
                        if(!key_exists(0,$accountMatches)){
                            continue;
                        }
                        preg_match('!فیش \d{6,8}!', $description, $firstReceiptNumberMatches);
                        if(!key_exists(0,$firstReceiptNumberMatches)){
                            continue;
                        }
                        preg_match('!\d{6,8}!', $firstReceiptNumberMatches[0], $secondReceiptNumberMatches);
                        if(!key_exists(0,$secondReceiptNumberMatches)){
                            continue;
                        }
                        $receiptNumber = $secondReceiptNumberMatches[0];
                        $peygiri = $erja = $receiptNumber;
                        $cardNumber = $accountMatches[0];
                    }
//                $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, trim($serial)]);
//                if (str_contains($amount, "-") || $stt != false || $cardNumber == null) {
//                    continue;
//                }else {
                    $statement[] = [
                        $user_id, // user_id
                        $banking_id, // banking_id
                        trim(str_replace(',', '', $amount) ?? ''), // amount
                        trim($erja ?? ''), // erja
                        trim($peygiri ?? ''), // peygiri
                        trim($serial ?? ''), // serial
                        trim($cardNumber ?? ''), // card_number
//                      $datetime = str_replace(["‪", "‬"], "", jdate($date)),  // datetime
//                      $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime), // bigint_datetime
                    ];
//                }
                }
            }
        } else {
            return false;
        }
        return array_reverse($statement, true);
    }
}

$parsianLogin = new ParsianLogin();

var_dump($parsianLogin->login());