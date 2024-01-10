<?php
load('http');

class shahr extends banking
{

    private $account;
    private $username;
    private $password;
    private $user_id;
    private $banking_id;
    private $http;
    private $needs_login_task = true;
    private $bankName = 'shahr';
    private $queryParams = [
        'ibReq' => 'WEB',
        'lang' => 'fa',
    ];
    private $cookieFile;
    private $captchaFile;
    private $testingBankingId;

    public function __construct(array $data, $user_id, $banking_id)
    {
        $GLOBALS['account'] = $this->account = $data['account']; //'4001002408872'
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->user_id = $user_id;
        $this->banking_id = $banking_id;
        $this->cookieFile = COOKIE_PATH . "$this->bankName-$this->banking_id.txt";
        $this->captchaFile = UPLOAD_PATH . "$this->bankName-captcha-$this->banking_id.jpg";
        $this->http = new HTTP();
        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0');
        $this->http->setCookieLocation($this->cookieFile);
        $this->http->setTimeout(50);
        $this->http->setVerbose(true);
        $this->testingBankingId = 1;
    }

    public function setProxy($config)
    {
        setBankingProxy($config, $this->bankName, $this->http);
    }

    public function logout()
    {
        $this->http->get('https://ebank.shahr-bank.ir/ebank/login/logout.action','get','','','');
        unlink($this->cookieFile);
        resetBankingProxy('shahr', $this->banking_id);
    }

    public function login()
    {
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(convertToString($this->isSignedIn()), 'isSignedIn');
        }
        if ($this->isSignedIn()) {
            return true;
        } else {
            $this->createNewLoginTask($this->banking_id);
            return false;
        }
    }


    function isSignedIn()
    {
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(json_encode([
                $this->account,
                $this->username,
                $this->password,
            ]), 'account');
        }
        $homeUrl = 'https://ebank.shahr-bank.ir/ebank/home/homePage.action';
        $homePage = $this->http->get($homeUrl, 'get', '', '', '');
        $logoutLink = "/ebank/login/logout.action";
        if (strpos($homePage, $logoutLink) !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function autoSigninStep1()
    {
        $signinPage = $this->getSigninPage();
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(json_encode($signinPage), 'signinPage');
        }
        if ($signinPage == null || $signinPage == "" || $signinPage == false) {
            $message = "Signin page didn't load currectly !!";
            $this->newLog($message, 'Signinpagedidntloadcurrectly');
            $this->logout();

            return [
                "message" => $message,
                "status" => false
            ];
        }
        $loginData = $this->getDataFromSigninPage($signinPage);

        if ($loginData['needs_captcha']) {
            // $loginData['captcha'] = decodeCaptcha($this->captchaFile, $this->bankName);
            // $captchaLen = strlen($loginData['captcha']);
            load('captcha-api');
            $api = new CaptchaAPI();
            $loginData['captcha'] = $api->solve($this->captchaFile);

            // if ($captchaLen < 4 || $captchaLen > 6) {
            //     return false;
            // }
        } else {
            $loginData['captcha'] = "";
        }
        //$this->newLog(json_encode($loginData), 'loginData');

        $sendSMSResponse = $this->sendSMSCodeToUser($loginData);
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(convertToString($sendSMSResponse), 'sendSMSResponse');
        }
//        $this->newLog(convertToString($sendSMSResponse), 'sendSMSResponse');

        $invalidCaptcha = 'لطفا کد امنیتی را درست وارد نمایید.';
        if (strpos($sendSMSResponse['data'], $invalidCaptcha) !== false) {
            $this->newLog("Bad captcha reported !!", 'badCaptchaReported');
            $api->reportBad();
            return false;
        }

        $errorInLogin = 'خطر امنیتی';
        if (strpos($sendSMSResponse['data'], $errorInLogin) !== false) {
            $this->newLog("security Error !!", 'securityError');
            $this->logout();
            return false;
        }

        if ($sendSMSResponse["status"] == false) {
            $this->newLog("ُSending SMS Failed!!", 'SendingSMSFailed');
            $this->logout();
            return false;
        }


        $loginData2 = [
            "struts.token.name" => "ticketLoginToken",
            "ticketResendTimerRemaining" => -1,
            "hiddenPass1" => 1,
            "hiddenPass2" => 2,
            "hiddenPass3" => 3,
        ];

        $loginData2["ticketLoginToken"] = getInputTag($sendSMSResponse["data"], '/<input type="hidden" name="ticketLoginToken" value=".*/');
        $loginData2["mobileNumber"] = getInputTag($sendSMSResponse["data"], '/<input type="hidden" class="" name="mobileNumber" id="mobileNumber" value=".*/');

        return $loginData2;
    }

    public function autoSigninStep2($data, $otp)
    {
        $data["ticketCode"] = $otp;
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(json_encode($data), 'autoSigninStep2');
        }
        if ($this->twoPhaseLogin($data)) {
            return true;
        } else {
            return false;
        }
    }

    public function logStatements($datetime = 'null', $amount = 'null')
    {
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog('enter to state', 'logStatements');
        }

        $selectDateUrl = 'https://ebank.shahr-bank.ir/ebank/viewAcc/partialDepositShow.action?daysAgo=30';
        $selectPageHtml = $this->http->get($selectDateUrl, 'get', '', '', '');

        if ($selectPageHtml == false) {
            return false;
        }
        $balanceData = [
            'struts.token.name' => "depositShowToken",
            'advancedSearch' => true,
            'maxLenForNote' => '200',
            'selectedDeposit' => $this->account,
            'selectedDepositValueType' => 'sourceDeposit',
            'selectedDepositIsComboValInStore' => false,
            'fromDateTime' => '1402/01/26  -  00:00',
            'toDateTime' => '1402/02/25  -  11:52',
            'order' => 'DESC',
        ];
        $balanceData['depositShowToken'] = getInputTag($selectPageHtml, '/<input type="hidden" name="depositShowToken" value=".*/');
        $balanceData['fromDateTime'] = getInputTag($selectPageHtml, '/<input type="text" name="fromDateTime" id="fromDateTime" value=".*/');
        $balanceData['toDateTime'] = getInputTag($selectPageHtml, '/<input type="text" name="toDateTime" id="toDateTime" value=".*/');
        $depositShowUrl = 'https://ebank.shahr-bank.ir/ebank/viewAcc/depositShow.action?' . http_build_query($balanceData);
        $depositShow = $this->http->get($depositShowUrl, 'post', '', $balanceData, '');
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog($depositShow, 'depositShow');
        }

        if ($depositShow == false) {
            return $depositShow;
        }
        $statements = getDeposit($depositShow, $this->user_id, $this->banking_id);
//        $this->newLog(json_encode($statements), 'statements');
        return $statements;
    }

    public function getBalances()
    {
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog("StartGetBalances", 'StartGetBalances');
        }
//        $balanceUrl = "https://ebank.shahr-bank.ir/ebank/viewAcc/viewDetailsAccountHtmlReport.action?currency=IRR&ownership=BE_TANHAYEE&personality=ACTUAL";
        $balanceUrl = "https://ebank.shahr-bank.ir/ebank/viewAcc/viewDetailsAccountHtmlReport.action?currency=IRR";
        $balanceResponse = $this->http->get($balanceUrl, 'get', '', '', '');
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog($balanceResponse, 'balanceResponse');
        }
        // get balance from html
        $balance = getBalance($balanceResponse, $this->account);
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(json_encode($balance), 'balance');
        }
        return $balance;
    }

    function getSigninPage()
    {
        $firstUrl = 'https://ebank.shahr-bank.ir/ebank/login/loginPage.action?ibReq=WEB';
        $firstResponse = $this->http->get($firstUrl, 'get', '', '', '');

//        $secondUrl = 'https://ebank.shahr-bank.ir/ebank/dispatcherNamespace/dispatcherAction.action?ibReq=WEB';
//        $secondResponse = $this->http->get($secondUrl, 'get', '', '', '');
//
//        $thirdResponse = $this->http->get($firstUrl, 'get', '', '', '');

        return $firstResponse;

    }

    function getDataFromSigninPage(string $signinPage)
    {
        $loginData = [
            'username' => $this->username,
            'password' => $this->password,
            'loginType' => 'STATIC_PASSWORD',
            'isSoundCaptcha' => 'false',
            'otpSyncRequired' => 'false',
            'soundCaptchaEnable' => 'true',
            'struts.token.name' => 'loginToken',
            'hiddenPass1' => $this->password, //'1',
            'hiddenPass2' => '2',
            'hiddenPass3' => '3',
        ];
        $loginData['loginToken'] = getInputTag($signinPage, '/<input type="hidden" name="loginToken" value=".*/');

        $captchaUrl = 'https://ebank.shahr-bank.ir/ebank/login/captcha.action?isSoundCaptcha=false&r='.rand(0,999999999);
        $captchaRawImage = $this->http->get($captchaUrl, 'get', '', '', '');
        $loginData['has_captcha'] = ($captchaRawImage != '') ? true : false;
        $loginData['needs_captcha'] = $loginData['has_captcha'];
        if ($loginData['has_captcha']) {
            writeOnFile($this->captchaFile, $captchaRawImage);
        }
        return $loginData;
    }

    function sendSMSCodeToUser(array $data)
    {
        $SMSUrl = "https://ebank.shahr-bank.ir/ebank/login/login.action?ibReq=WEB&lang=fa";
        $SMSResponse = $this->http->get($SMSUrl, 'post', 'https://ebank.shahr-bank.ir/ebank/login/loginPage.action?ibReq=WEB', $data, '');
        $textForSms = "لطفا بلیت امنیتی ارسال شده به تلفن همراه";

        if (!$SMSResponse) {
            return [
                "data" => '',
                "message" => "Sending SMS failed !!",
                "status" => false
            ];
        }
        if (strpos($SMSResponse, $textForSms) == false) {
            return [
                "data" => $SMSResponse,
                "message" => "Sending SMS failed !!",
                "status" => false
            ];
        }
        return [
            "data" => $SMSResponse,
            "message" => "Sending SMS successfully !!",
            "status" => true
        ];
    }

    public function getCodeFromSMS($messages, $type = 1)
    {
        if ($type == 1) { // for login
            foreach ($messages as $message) {
                if ((strpos($message['message'], 'بانک شهر') !== false) || (strpos($message['message'], 'ورود') !== false)) {
                    preg_match_all('!\d{6}!', $message['message'], $matches);
                    if (isset($matches[0][0])) {
                        return $matches[0][0];
                    }
                }
            }
        } else if ($type == 2) { // for paya transfer
            foreach ($messages as $message) {
                if ((strpos($message['message'], 'بانک شهر') !== false) || (strpos($message['message'], 'پایا') !== false)) {
                    preg_match_all('! \d{6}! ', $message['message'], $matches);
                    if (isset($matches[0][0])) {
                        return trim($matches[0][0]);
                    }
                }
            }
        } else {
            // undefined type
            return false;
        }

        return false;
    }

    public function twoPhaseLogin(array $twoPhaseData)
    {
        $loginUrl2 = 'https://ebank.shahr-bank.ir/ebank/login/twoPhaseLoginWithTicket.action?' . http_build_query($this->queryParams);
        $loginResponse2 = $this->http->get($loginUrl2, 'post', '', $twoPhaseData, '');

        $urlCheckUsername = 'https://ebank.shahr-bank.ir/ebank/login/checkUsername.action';
        $checkUsernameResponse = $this->http->get($urlCheckUsername, 'get', '', '', '');

        $urlCheckPassword = 'https://ebank.shahr-bank.ir/ebank/login/checkPassword.action';
        $checkPasswordResponse = $this->http->get($urlCheckPassword, 'get', '', '', '');

        $urlCompleteLogin = 'https://ebank.shahr-bank.ir/ebank/login/completeLogin.action';
        $completeLoginResponse = $this->http->get($urlCompleteLogin, 'get', '', '', '');

        return [
            $loginResponse2,
            $checkUsernameResponse,
            $checkPasswordResponse,
            $completeLoginResponse,
        ];

    }

    private function newLog($text, $caller)
    {
        newLog($text, 'shahr-' . $this->banking_id . '-' . $caller, 'shahr');
    }

    public function getTransferRemainingLimit()
    {
        $newNormalAchUrl = 'https://ebank.shahr-bank.ir/ebank/transfer/newNormalAch.action';
        $newNormalAchData = [
            'showInfoPage' => "true",
        ];

        $newNormalAchUrlResponse = $this->http->get($newNormalAchUrl, 'post', '', $newNormalAchData, '');
        $newNormalAchUrlResponse = convertPersianNumberToEnglish($newNormalAchUrlResponse);
        preg_match_all('/<div class="item-field-info">(.*?)<\/div>/s', $newNormalAchUrlResponse, $matches);

        $output = preg_replace('/[^0-9]/', '', $matches[0]);
        if (isset($output[0])) {
            return [
                'paya' => $output[0],
                'acc' => $output[0],
            ];
        } else {
            return false;
        }
    }

    public function payaTransfer($iban, $amount, $name, $surname, $desc = '')
    {
        error_reporting(E_ALL);
        ini_set('display_errors',1);
        /*$this->newLog(json_encode([
            $iban,
            $amount,
            $name,
            $surname,
            $desc
        ]),'payaTransfer');*/
        $newNormalAchUrl = 'https://ebank.shahr-bank.ir/ebank/transfer/newNormalAch.action';
        $newNormalAchData = [
            'showInfoPage' => "true",
        ];

        $this->newLog('params:'.var_export($newNormalAchData,true),'payaTransfer');
        $newNormalAchUrlResponse = $this->http->get($newNormalAchUrl, 'post', '', $newNormalAchData, '');
        $this->newLog('response:'.var_export($newNormalAchUrlResponse,true),'payaTransfer');
        $pattern = '/<input type="hidden" name="normalAchTransferToken" value="(.*?)">/s';
        $normalAchTransferToken = getInputTag($newNormalAchUrlResponse,$pattern);

        $normalAchTransferUrl =  "https://ebank.shahr-bank.ir/ebank/transfer/normalAchTransfer.action";
        $normalAchTransferData = [
            "transferType" => "NORMAL_ACH",
            "struts.token.name" => "normalAchTransferToken",
            "normalAchTransferToken" => $normalAchTransferToken,
            "sourceSaving" => $this->account,
            "sourceSavingValueType" => "sourceDeposit",
            "sourceSavingPinnedDeposit" => "",
            "sourceSavingIsComboValInStore" => "false",
            "destinationIbanNumber" => $iban,
            "destinationIbanNumberValueType" => "",
            "destinationIbanNumberPinnedDeposit" => "",
            "destinationIbanNumberIsComboValInStore" => false,
            "owner" => "$name $surname",
            "amount" => $amount,
            "currency" => "",
            "currencyDefaultFractionDigits" => "",
            "reason" => "DRPA",
            "factorNumber" => "",
            "remark" => $desc
        ];
        $this->newLog('params:'.var_export($normalAchTransferData,true),'payaTransfer');
        $newNormalAchUrlResponse = $this->http->get($normalAchTransferUrl, 'post', '', $normalAchTransferData, '');
        $this->newLog('response:'.var_export($newNormalAchUrlResponse,true),'payaTransfer');

        $pattern = '/<meta name="CSRF_TOKEN" content=(.*?)>/s';
        $CSRF_TOKEN = getMetaTag($newNormalAchUrlResponse,$pattern);
        $generateTicketdata = [
            "CSRF_TOKEN"=> $CSRF_TOKEN,//"hSDNNS/pe+AA5+7RFXQfjArvd7BaierrMnCYW5+u2gQ=",
            "ticketAmountValue" => $amount,
            "ticketModernServiceType" => "NORMAL_ACH_TRANSFER",
            "ticketParameterResourceType" => "DEPOSIT",
            "ticketParameterResourceValue" => $this->account,
            "ticketParameterDestinationType" => "IBAN",
            "ticketParameterDestinationValue" => $iban,
            "ticketDestinationName" => "$name . $surname",
            "ticketAdditionalInfoAmount"=> ""
        ];
        $generateTicketUrl = "https://ebank.shahr-bank.ir/ebank/general/generateTicket.action?".http_build_query($generateTicketdata);
        $this->newLog('params:'.var_export($generateTicketdata,true),'payaTransfer');
        $generateTicketResponse = $this->http->get($generateTicketUrl, 'get', '', '', '');
        $generateTicketResponse = json_decode($generateTicketResponse,true);
        $this->newLog('response:'.var_export($generateTicketResponse,true),'payaTransfer');
        $pattern = '/<input type="hidden" name="normalAchTransferConfirmToken" value="(.*?)">/s';
        $normalAchTransferConfirmToken = getInputTag($newNormalAchUrlResponse,$pattern);
        if($generateTicketResponse['resultType'] === "success"){
            $data = [
                'iban' => $iban,
                'amount' => $amount,
                'name' => $name,
                'surname' => $surname,
                'desc' => $desc,
                'normalAchTransferConfirmToken' => $normalAchTransferConfirmToken,
            ];
            return [
                'status' => 1,
                'data' => $data,
            ];
        }else{
            return [
                'status' => 0,
                'error' => var_export($generateTicketResponse,true),
            ];
        }

    }

    public function payaTransferStep2(array|bool $data, $otp)
    {
        if((!$otp) || strlen($otp) === 0 || $otp === null){
            newLog("There is not code",'noOTPCode');
            return [
                'status' => 0,
                'error' => 'There is not otp code',
            ];
        }

        if($data === false){
            newLog("There is Data for payaTransferStep2",'noDataForPayaTransferStep2');
            return [
                'status' => 0,
                'error' => "There is Data for payaTransferStep2",
            ];
        }
        $normalAchTransferUrl = "https://ebank.shahr-bank.ir/ebank/transfer/normalAchTransfer.action";

        $normalAchTransferData = [
            "struts.token.name" => "normalAchTransferConfirmToken",
            "normalAchTransferConfirmToken" => $data['normalAchTransferConfirmToken'],
            "transferType" => "NORMAL_ACH",
            "sourceSaving" => $this->account,
            "destinationIbanNumber" => $data["iban"],
            "owner" => $data['name'] . " " . $data['surname'],
            "amount" => $data['amount'],
            "currency" => "IRR",
            "reason" => "DRPA",
            "factorNumber" => "",
            "remark" => "",
            "hiddenPass1"=> "1",
            "hiddenPass2" => "2",
            "hiddenPass3" => "3",
            "ticketRequired" => "true",
            "ticketResendTimerRemaining" => "15",
            "ticket" => $otp,
            "back" => "back",
            "perform" => "ثبت انتقال وجه",//"ثبت+انتقال+وجه"
        ];

        $newNormalAchUrlResponse = $this->http->get($normalAchTransferUrl, 'post', '', $normalAchTransferData, '');

        if($newNormalAchUrlResponse == "" || $newNormalAchUrlResponse == null)
        {
            return [
                'status' => 'unknown',
                'debug' => $newNormalAchUrlResponse."\n\n".$this->http->getVerboseLog(),
            ];
        }

        if(strpos($newNormalAchUrlResponse, 'درخواست انتقال وجه بین بانکی پایا عادی ثبت شد.') !== false)
        {
            $newNormalAchUrlResponse = convertPersianNumberToEnglish($newNormalAchUrlResponse);
            preg_match_all('/<div class="formSection noTitleSection transferReceipt" id="">(.*?)<div class="commandBar/s', $newNormalAchUrlResponse, $matches1);
            preg_match_all('/<span class="form-item-field " id="">(.*?)<\/span>/s', $matches1[0][0], $matches2);
            $name = $matches2[0][5];
            preg_match_all('!\d{19,21}!', $matches2[0][0], $matches3);
            $peygiri = $matches3[0][0];
            preg_match_all('/به نام (.*?)<\/span>/s', $name, $matches4);
            $dest = trim($matches4[1][0]);

            return [
                'status' => 1,
                'peygiri' => $peygiri,
                'dest' => $dest,
            ];
        }

        if(strpos($newNormalAchUrlResponse, 'مبلغ" بیش از مبلغ تعیین شده سقف روزانه است') !== false)
        {
            return [
                'status' => 0,
                'error' => 'مبلغ" بیش از مبلغ تعیین شده سقف روزانه است',
            ];
        }

        elseif(strpos($newNormalAchUrlResponse, 'بلیط امنیتی نامعتبر است ، لطفا آن را به درستی وارد نمایید.'))
        {
            return [
                'status' => 0,
                'error' => 'بلیط امنیتی نامعتبر است ، لطفا آن را به درستی وارد نمایید.',
            ];
        }

        else{
            return [
                'status' => 'unknown',
                'debug' => $newNormalAchUrlResponse."\n\n".$this->http->getVerboseLog(),
            ];
        }
    }
}