<?php
load('http');
load('date');

class parsian extends banking{

    private $account;
    private $originalAccountNumber;
    private $username;
    private $password;
    private $pin2;
    private $user_id;
    private $banking_id;
    private $http;
    public $needs_login_task = true;
    private $bankName = 'parsian';
    private $cookieFile;
    private $captchaFile;
    private $captchaFile2;
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
        $this->pin2 = @$data['secondPass'];
        $this->user_id = $user_id;
        $this->banking_id = $banking_id;
        $this->cookieFile = COOKIE_PATH . "$this->bankName-$this->banking_id.txt";
        $this->captchaFile = UPLOAD_PATH. "$this->bankName-captcha-$this->username.jpg";
        $this->captchaFile2 = UPLOAD_PATH. "$this->bankName-captcha2-$this->username.jpg";
        $this->http = new HTTP();
        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0');
        $this->http->setCookieLocation($this->cookieFile);
        $this->http->setTimeout(50);
        $this->http->setVerbose(true);
        $this->loginData = $this->getLoginData();
        $this->loginData2 = $this->getLoginData2();
        $this->testingBankingId = 1561;
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
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($homePage, true), "homePage-check");
        }
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
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($signinPage, true), "signinPage");
        }
        if($signinPage == "" || !$signinPage){
            $this->newLog('Failed to load the sign in page !!',"failedToLoadTheSignInPage");
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
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($loginResponse, true), "loginResponse");
        }
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
        $header = [
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ];
        $this->http->setHeaders($header);
        $statementResponse = $this->http->get($statementUrl,'post','',json_encode($statementData),'');
        $this->http->setHeaders([]);
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($statementResponse, true), "statementResponse");
        }
        $isValid = notNeedLogout($statementResponse);

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
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($getAllAccountsResponse, true), "getAllAccountsResponse");
        }
        $isValid = notNeedLogout($getAllAccountsResponse);
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
                if((strpos($message['message'],'رمز ورود به اینترنت بانک یا همراه بانک') !== false)) {
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
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($loginResponse2,true),"loginResponse2");
        }
        if ($loginResponse2 == $this->incorrectSmsCode) {
            $this->newLog('SMS code is incorrect!!!',"SMSCodeIsIncorrect");
            $this->logout();
            return false;
        }
        $homeUrl = "https://ipb.parsian-bank.ir/";
        $homeResponse = $this->http->get($homeUrl,'get','','','');
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($homeResponse, true), "homeResponse");
        }
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
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($newNormalAchUrlResponse, true), "newNormalAchUrlResponse");
        }
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

    public function payaTransfer($iban, $amount, $name, $surname, $desc = '')
    {
        $captcha = "";
        if(!$iban || strlen($iban) !== 26)
        {
            $message = 'شبا تعریف نشده است یا شبای تعریف شده صحیح نمیباشد!';
            $this->newLog(var_export([$message,$iban],true),"notFoundIban");
            return [
                'status' => 0,
                'error' => $message,
            ];
        }
        if($amount <= 0)
        {
            $message = 'مبلغ تعریف شده صحیح نمیباشد!';
            $this->newLog(var_export($message,true),"notFoundAmount");
            return [
                'status' => 0,
                'error' => $message,
            ];
        }
        $formattedSheba = setPayaFormatForSheba($iban);
        $header = [
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ];
        $integratedAmount = (int) $amount;

        $ibanInquiryByCentralBankUrl = "https://ipb.parsian-bank.ir/account/ibanInquiryByCentralBank";
        $ibanInquiryByCentralBankData = [
            'iban' => $formattedSheba,
            'paymentId' => "",
            'amount' => $integratedAmount,
            'captcha' => $captcha
        ];


        $this->http->setHeaders($header);
        $ibanInquiryByCentralBankResponse = $this->http->get($ibanInquiryByCentralBankUrl, 'post', '', json_encode($ibanInquiryByCentralBankData),'');
        $this->http->setHeaders([]);
        $ibanInquiryByCentralBankResponseHeader = $this->http->getResponseHeaders();
        if(array_key_exists('exceptionType',$ibanInquiryByCentralBankResponseHeader))
        {
            $exceptionType = urldecode($ibanInquiryByCentralBankResponseHeader["exceptionType"]) ?? null;
            $this->newLog(var_export($exceptionType,true),"ibanInquiryByCentralHeader");
//        "کدامنيتيواردشدهصحيحنميباشد.";
//            if($exceptionType == "کدامنيتيواردشدهصحيحنميباشد."){
//                $forVerificationCaptcha = "https://ipb.parsian-bank.ir/vendors/captcha/forVerification";
//                https://ipb.parsian-bank.ir/customer/isLoadControlEnabled captchaEnabled	false
//            }
        }
//        else{
//            $this->newLog(var_export($ibanInquiryByCentralBankResponseHeader,true),"ibanInquiryByCentralHeader2");
//        }
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($ibanInquiryByCentralBankResponse, true), "ibanInquiryByCentralBankResponse");
        }
        $ibanInquiryByCentralBank = json_decode($ibanInquiryByCentralBankResponse);
        $ownerName = $ibanInquiryByCentralBank?->ownerName ?? $name;
        $ownerFamily = $ibanInquiryByCentralBank?->ownerFamily ?? $surname;

        $validateBalanceThresholdUrl = "https://ipb.parsian-bank.ir/account/validateBalanceThreshold/";
        $validateBalanceThresholdData = [
            "accountNumber" => $this->originalAccountNumber,//"470-01508868-601",
            "amount" => $integratedAmount
        ];

        $this->http->setHeaders($header);
        $validateBalanceThresholdResponse = $this->http->get($validateBalanceThresholdUrl, 'post', '', json_encode($validateBalanceThresholdData),'');
        $this->http->setHeaders([]);
        $validateBalanceThreshold = json_decode($validateBalanceThresholdResponse,true);

        if($validateBalanceThreshold["underThreshold"] == true)
        {
            $message = 'مبلغ بیش از مبلغ تعیین شده سقف روزانه است یا موجودی کافی نمی باشد.';
            $this->newLog(var_export($message,true),"notEnoughFound");
            return [
                'status' => 0,
                'error' => $message,
            ];
        }

        $generateUniqueTrackingCodeUrl = "https://ipb.parsian-bank.ir/generateUniqueTrackingCode";
        $generateUniqueTrackingCodeData = [
            "transferType" => "AST",
        ];
        $this->http->setHeaders($header);
        $generateUniqueTrackingCodeResponse = $this->http->get($generateUniqueTrackingCodeUrl, 'post', '', json_encode($generateUniqueTrackingCodeData),'');
        $this->http->setHeaders([]);
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($generateUniqueTrackingCodeResponse, true), "generateUniqueTrackingCodeResponse");
        }

        $generateUniqueTrackingCode = json_decode($generateUniqueTrackingCodeResponse);
        $uniqueTrackingCode = $generateUniqueTrackingCode?->uniqueTrackingCode ?? null;
        $result = [
            'iban' => $iban,
            'amount' => $integratedAmount,
            'name' => $ownerName,
            'surname' => $ownerFamily,
            'desc' => $desc,
            'uniqueTrackingCode' => $uniqueTrackingCode,
            'captcha' => $captcha,
        ];
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export([
                'status' => 1,
                'data' => $result,
                'noNeedSMS' => true,
            ], true), "payaTransferResult");
        }
        return [
            'status' => 1,
            'data' => $result,
            'noNeedSMS' => true,
        ];


    }

    public function payaTransferStep2(array|bool $data, $otp)
    {
        $header = [
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ];

        if($otp == 'noNeedSMS') {
            $otp = $this->pin2;
        }
        $formattedSheba = setPayaFormatForSheba($data['iban']);
        $polFundTransferUrl = "https://ipb.parsian-bank.ir/account/polFundTransfer";
        $polFundTransferData = [
            "paymentId" => "",
            "sourceAccountNumber" => $this->originalAccountNumber,
            "destinationIban" => $formattedSheba,
            "transferAmount" =>  $data['amount'],
            "paymentType" => "DRPA",
            "destinationIbanOwnerName" => $data['name'] . " " . $data['surname'],
            "captcha" => $data['captcha'],
            "uniqueTrackingCode" => $data['uniqueTrackingCode'],
            "passType" =>"S",
            "pass" => $otp
        ];
        $this->http->setHeaders($header);
        $polFundTransferResponse = $this->http->get($polFundTransferUrl, 'post', '', json_encode($polFundTransferData),'');
        $this->http->setHeaders([]);
        $polFundTransferHeader = $this->http->getResponseHeaders();
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($polFundTransferResponse, true), "polFundTransferResponse");
        }
        $polFundTransfer = json_decode($polFundTransferResponse,true);

        if(array_key_exists('statusCode' , $polFundTransfer)){
            if($polFundTransfer["statusCode"] == "ACCP"){
                return [
                    'status' => 1,
                    'peygiri' => $polFundTransfer['trackingCode'],
                    'dest' => 'Successful: Received bank name: '.$polFundTransfer['receiverBankName']. " Transaction id: ".$polFundTransfer['transactionId'],
                ];
            }
            if($polFundTransfer["statusCode"] == "PEND"){
                $inquiryPolTransferUrl = "https://ipb.parsian-bank.ir/report/inquiryPolTransfer";
                $inquiryPolTransferData = [
                    "transactionId" => $polFundTransfer['transactionId']
                ];
                $this->http->setHeaders($header);
                $inquiryPolTransferResponse = $this->http->get($inquiryPolTransferUrl, 'post', '', json_encode($inquiryPolTransferData),'');
                $this->http->setHeaders([]);
                if ($this->banking_id == $this->testingBankingId) {
                    $this->newLog(var_export($inquiryPolTransferResponse, true), "inquiryPolTransferResponse");
                }
                $inquiryPolTransfer = json_decode($inquiryPolTransferResponse,true);
                $numberOfTry = 5;
                sleep(10);
                while ($inquiryPolTransfer['polEntries'][0]['status'] == "PEND") {
                    sleep(5);
                    $numberOfTry--;
                    $this->http->setHeaders($header);
                    $inquiryPolTransferResponse = $this->http->get($inquiryPolTransferUrl, 'post', '', json_encode($inquiryPolTransferData), '');
                    $this->http->setHeaders([]);
                    $this->newLog(var_export($inquiryPolTransferResponse, true), "inquiryPolTransferResponse2222");
                    $inquiryPolTransfer = json_decode($inquiryPolTransferResponse,true);
                    if($inquiryPolTransfer['polEntries'][0]['status'] == "ACCP" || $numberOfTry <= 0){
                        break;
                    }
                }
                if($inquiryPolTransfer['polEntries'][0]['status'] == 'ACCP'){
                    return [
                        'status' => 1,
                        'peygiri' => $inquiryPolTransfer['polEntries'][0]['referenceId'],
                        'dest' => $inquiryPolTransfer['polEntries'][0]['statusDescription'],
                    ];
                }else{
                    return [
                        'status' => 'unknown',
                        'debug' => $inquiryPolTransfer."\n\n".$this->http->getVerboseLog(),
                    ];
                }
            }

        }
        else if (array_key_exists('error' , $polFundTransfer)){
            $exceptionType = urldecode($polFundTransferHeader["exceptionType"]) ?? null;
            $errorMessage = $polFundTransfer["error"] . ': ' . $polFundTransfer["message"] . "--" . $exceptionType ?? null;
            return [
                'status' => 0,
                'error' => var_export($errorMessage,true),
            ];
        }
        else{
            return [
                'status' => 'unknown',
                'debug' => $polFundTransfer."\n\n".$this->http->getVerboseLog(),
            ];
        }
    }

}