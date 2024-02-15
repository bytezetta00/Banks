<?php
load('http');
load('date');

class parsian extends banking{

    private $account;
    private $originalAccountNumber;
    private $username;
    private $password;
    private $user_id;
    private $banking_id;
    private $http;
    public $needs_login_task = true;
    private $bankName = 'parsian';
    private $cookieFile;
    private $captchaFile;
    private $loginData;
    private $loginData2;
    private $incorrectCaptchaCode = 1002;
    private $incorrectSmsCode = 1003;
    private $testingBankingId;


    public function __construct(array $data,$user_id ,$banking_id)
    {
        $GLOBALS['account'] = $this->account = str_replace(['-',' '],[''],$data['account']);
        $this->originalAccountNumber = $data['account'];
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->user_id = $user_id;
        $this->banking_id = $banking_id;
        $this->cookieFile = COOKIE_PATH . "$this->bankName-$this->banking_id.txt";
        $this->captchaFile = UPLOAD_PATH. "$this->bankName-captcha-$this->username.jpg";
        $this->http = new HTTP();
        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0');
        $this->http->setCookieLocation($this->cookieFile);
        $this->http->setTimeout(50);
        $this->http->setVerbose(true);
        $this->loginData = $this->getLoginData();
        $this->loginData2 = $this->getLoginData2();
        $this->testingBankingId = 1440;
    }

    public function setProxy($config) {
        setBankingProxy($config, $this->bankName, $this->http);
    }

    private function newLog($text,$caller)
    {
        newLog($text,"$this->bankName-$this->banking_id-$caller",$this->bankName);
    }

    public function logout()
    {
        $logoutUrl = "https://ipb.parsian-bank.ir/logout";
        $this->http->get($logoutUrl,'get','','','');
        unlink($this->cookieFile);
        resetBankingProxy($this->bankName, $this->banking_id);
    }

    public function login()
    {
        if($this->isSignedIn()) {
            return true;
        } else {
            $this->createNewLoginTask($this->banking_id);
            return false;
        }
    }
    function isSignedIn()
    {
        $homeUrl = "https://ipb.parsian-bank.ir/";
        $homePage = $this->http->get($homeUrl,'get','','','');
        $logoutLink = "/logout";
        $getUserLink = "/getoff/user";
//        $this->newLog(var_export($homePage,true),"homePage-check");
        if(strpos($homePage, $logoutLink) !== false || strpos($homePage, $getUserLink) !== false) {
            return true;
        } else {
            $this->logout();
            return false;
        }

    }

    public function autoSigninStep1()
    {
        $loginHtmlUrl = "https://ipb.parsian-bank.ir/login.html";
        $signinPage = $this->http->get($loginHtmlUrl,'get','','','');
        if($signinPage == "" || !$signinPage){
            $this->newLog('Failed to load the sign in page !!',"failedToSendSMS");
            $this->logout();
            return false;
        }

        $forLoginUrl = "https://ipb.parsian-bank.ir/vendors/captcha/forLogin";
        $captchaResponse = $this->http->get($forLoginUrl,'get','','','');

        if(!$captchaResponse){
            $this->newLog('Failed to load captcha page !!',"failedToLoadCaptcha");
            $this->logout();
            return false;
        }
        else{
            writeOnFile($this->captchaFile, $captchaResponse);
            load('captcha-api');
            $api = new CaptchaAPI();
            $this->loginData['captcha'] = $api->solve($this->captchaFile);
        }
        $loginUrl = "https://ipb.parsian-bank.ir/login";
        $loginResponse = $this->http->get($loginUrl,'post','',$this->loginData,'');
//        $this->newLog(var_export($loginResponse,true),"loginResponse");

        if ($loginResponse == $this->incorrectCaptchaCode) {
            $this->newLog('Captcha is incorrect!!!',"CaptchaIsIncorrect");
            $this->logout();
            return false;
        }
        $this->loginData2['challengeKey'] = $loginResponse;

        return $this->loginData2;
    }

    public function autoSigninStep2(?array $data,int|string $otp)
    {
        $data["otpPassword"] = $otp;
        if($this->twoPhaseLogin($data)) {
            return true;
        } else {
            return false;
        }
    }

    public function logStatements($datetime='null', $amount='null')
    {
        $statementUrl = "https://ipb.parsian-bank.ir/account/statement";
        $statementData = [
            "accountNumber" => $this->account,
            "fromDate" => getFormattedPreviousMonthDate(),//"2023-12-19T20:00:00.000Z",// 1 month before
            "toDate" => getFormattedCurrentDate(),//"2024-01-06T09:29:13.896Z", // current month
        ];
//        $this->newLog(var_export($statementData,true),"statementData");

        $header = [
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ];
        $this->http->setHeaders($header);
        $statementResponse = $this->http->get($statementUrl,'post','',json_encode($statementData),'');
        $this->http->setHeaders([]);
//        $this->newLog(var_export($statementResponse,true),"statementResponse");
        $isValid = notNeedLogout($statementResponse);
//        $this->newLog(var_export($isValid,true),"statementValid");
        if(!$isValid){
            $this->newLog("statements response is invalid!!!: $isValid","statementsResponseIsInvalid");
            $this->logout();
            return false;
        }
        $statements = getDeposits($statementResponse,$this->user_id,$this->banking_id);

        if(!$statements){
            $this->newLog('Failed to read statement from html or there is no statement!!',"failedToReadStatement");
            return [];
        }
        return  $statements;
    }

    public function getBalances()
    {
        $getAllAccountsUrl = "https://ipb.parsian-bank.ir/account/getAllAccounts";
        $getAllAccountsData = [
            "currency" => "IRR"
        ];
        $header = [
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ];
        $this->http->setHeaders($header);
        $getAllAccountsResponse = $this->http->get($getAllAccountsUrl,'post','',json_encode($getAllAccountsData),'');
        $this->http->setHeaders([]);
//        $this->newLog(var_export($getAllAccountsResponse,true),"getAllAccountsResponse");
        $isValid = notNeedLogout($getAllAccountsResponse);
//        $this->newLog(var_export($isValid,true),"balanceValid");
        if(!$isValid){
            $this->newLog("balance response is invalid!!!: $isValid","balanceResponseIsInvalid");
            $this->logout();
            return false;
        }

        $balance = getBalance($getAllAccountsResponse,$this->account);

        if(!$balance){
            $this->newLog('Failed to read balance from html or there is no account!!',"failedToReadBalance");
            return false;
        }
        $this->newLog(var_export($balance,true),"balance");

        return $balance;
    }

    public function getCodeFromSMS($messages,$type=1)
    {
        if($type == 1){ // for login
            foreach($messages as $message) {
                if((strpos($message['message'],'بانک') !== false) && (strpos($message['message'],'ورود') !== false)) {
                    preg_match('!\d{5}!', $message['message'], $matches);
                    if(isset($matches[0])) {
                        return $matches[0];
                    }
                }
            }
        }
        else if($type == 2){ // for paya transfer
            foreach($messages as $message) {
                if((strpos($message['message'],'بانک پارسیان') !== false) && (strpos($message['message'],'پایا') !== false)) {
                    preg_match('!\d{5}!', $message['message'], $matches);
                    if(isset($matches[0])) {
                        return $matches[0];
                    }
                }
            }
        }
        else{
            // undefined type
            return false;
        }

        return false;
    }

    public function twoPhaseLogin(array $twoPhaseData)
    {
        $loginUrl = "https://ipb.parsian-bank.ir/login";
        $loginResponse2 = $this->http->get($loginUrl,'post','',$twoPhaseData,'');
//        $this->newLog(var_export($loginResponse2,true),"loginResponse2");
        if ($loginResponse2 == $this->incorrectSmsCode) {
            $this->newLog('SMS code is incorrect!!!',"SMSCodeIsIncorrect");
            $this->logout();
            return false;
        }
        $homeUrl = "https://ipb.parsian-bank.ir/";
        $homeResponse = $this->http->get($homeUrl,'get','','','');
//        $this->newLog(var_export($homeResponse,true),"homeResponse");

        return $homeResponse;
    }

    public function getLoginData()
    {
        return [
            "challengeKey" => "",
            "langKey" => "fa",
            "browserMode" => "public",
            "otpInProgress" => "false",
            "currentStep" => "1",
            "pib_username" => $this->username,
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
            "pib_username" => $this->username,
            "pib_password" => $this->password,
            "passwordType" => "S",
            //"otpPassword" => "",
            "captcha" => "bbxhc",
        ];
    }

    public function getTransferRemainingLimit()
    {
        $getTransferLimitationsUrl = "https://ipb.parsian-bank.ir/customer/getTransferLimitations?type=";
        $getTransferLimitationsData = [
            'type' => "ACH_NORMAL_TRANSFER"
        ];
        $header = [
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ];
        $this->http->setHeaders($header);
        $newNormalAchUrlResponse = $this->http->get($getTransferLimitationsUrl, 'post', '', json_encode($getTransferLimitationsData), '');
        $this->http->setHeaders([]);
        $getTransferLimitations = json_decode($newNormalAchUrlResponse);
        $remainedTodayWithdraw = $getTransferLimitations?->remainedTodayWithdraw ?? null;
        if (isset($remainedTodayWithdraw)) {
            return [
                'paya' => $remainedTodayWithdraw,
                'acc' => $remainedTodayWithdraw,
            ];
        } else {
            return false;
        }
    }
}