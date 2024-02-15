<?php
load('http');
load('date');

class sina extends banking{

    private $account;
    private $username;
    private $password;
    private $user_id;
    private $banking_id;
    private $http;
    public $needs_login_task = true;
    private $bankName = 'sina';
    private $cookieFile;
    private $captchaFile;
    private $testingBankingId;

    public function __construct(array $data,$user_id ,$banking_id)
    {
        $GLOBALS['account'] = $this->account = $data['account']; //'4001002408872'
        $this->username = $data['username'];
        $this->password = $data['password'];
        //$this->holder = $data['holder'];
        $this->user_id = $user_id;
        $this->banking_id = $banking_id;
        $this->cookieFile = COOKIE_PATH . "$this->bankName-$this->banking_id.txt";
        $this->captchaFile = UPLOAD_PATH. "$this->bankName-captcha-$this->username.jpg";
        $this->http = new HTTP();
        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0');
        $this->http->setCookieLocation($this->cookieFile);
        $this->http->setTimeout(50);
        $this->http->setVerbose(true);
        $this->testingBankingId = 1;
    }

    public function setProxy($config) {
        setBankingProxy($config, $this->bankName, $this->http);
    }

    private function newLog($text,$caller)
    {
        newLog($text,'sina-'.$this->banking_id.'-'.$caller,'sina');
    }

    public function logout()
    {
        $this->http->get('https://ib.sinabank.ir/webbank/login/logout.action','get','','','');
        unlink($this->cookieFile);
        resetBankingProxy('sina', $this->banking_id);
    }

    public function login()
    {
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(convertToString($this->isSignedIn()), 'isSignedIn');
        }
        if($this->isSignedIn()) {
            return true;
        } else {
            $this->createNewLoginTask($this->banking_id);
            return false;
        }
    }


    function isSignedIn()
    {
//        if ($this->banking_id == $this->testingBankingId) {
//            $this->newLog(json_encode([
//                $this->account,
//                $this->username,
//                $this->password,
//            ]), 'account');
//        }
        $homeUrl = 'https://ib.sinabank.ir/webbank/home/homePage.action';
        $homePage = $this->http->get($homeUrl,'get','','','');
        $logoutLink = "/webbank/login/logout.action";
        if(strpos($homePage, $logoutLink) !== false) {
            return true;
        } else {
            $this->logout();
            return false;
        }
    }

    public function autoSigninStep1()
    {
        $signinPage = $this->getSigninPage();
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($signinPage,true),"signinPage");
        }
        if($signinPage == null || $signinPage == "" || $signinPage == false){
            $this->newLog('Failed to load the signin page !!',"failedToSendSMS");
            $this->logout();
            return false;
        }

        $loginData = $this->getDataFromSigninPage($signinPage);

        if($loginData['needs_captcha']){
            // $loginData['captcha'] = decodeCaptcha($this->captchaFile, $this->bankName);
            // $captchaLen = strlen($loginData['captcha']);

            load('captcha-api');
            $api = new CaptchaAPI();
            $loginData['captcha'] = $api->solve($this->captchaFile);
        }else{
            $loginData['captcha'] = "";
        }

        $sendSMSResponse = $this->sendSMSCodeToUser($loginData);
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(json_encode($loginData), 'loginData');
        }
        if(!$sendSMSResponse["status"]){
            $this->newLog('Failed To Send SMS !!',"failedToSendSMS");
            $this->logout();
            return false;
        }
        if($sendSMSResponse["status"] == true && $sendSMSResponse["message"] == "noNeedSMS"){
            return "noNeedSMS";
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

    public function autoSigninStep2($data,$otp)
    {
        $data["ticketCode"] = $otp;
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(json_encode($data), 'data');
        }
        if($this->twoPhaseLogin($data)) {
            return true;
        } else {
            return false;
        }
    }

    public function logStatements($datetime='null', $amount='null')
    {
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog($this->account, 'logStatements');
        }
        $account = $this->account;
        $depositShowUrl = "https://ib.sinabank.ir/webbank/viewAcc/defaultBillList.action?selectedDeposit=$account&accountType=JARI_ACCOUNT&currency=IRR";
        $depositShowHtml = $this->http->get($depositShowUrl,'get','','','');
        if($depositShowHtml == false){
            return false;
        }

        $depositShowToken = getInputTag($depositShowHtml,'/<input type="hidden" name="depositShowToken" value=".*/');
        $stmtIdnote1 = getInputTag($depositShowHtml,'/<input type="hidden" class="" name="stmtIdnote1" id="stmtIdnote1" value=".*/');

        $now = jdate('Y/m/d');
        $nowDate = "$now  -  23:59";
        $aMonthAgoTimestamp = strtotime("-1 month");
        $aMonthAgo = jdate('Y/m/d', $aMonthAgoTimestamp);
        $aMonthAgoDate = "$aMonthAgo  -  00:00";

        $depositShowData = [
            "struts.token.name"=>"depositShowToken",
            "depositShowToken"=>$depositShowToken,//"JWDF471CGPGBZJIHO75ZIE67VA78384Y",
            "advancedSearch"=>"true",
            "personalityType"=>"",
            "depositGroupByReq"=>"",
            "referenceCustomerName"=>"",
            "referenceCif"=>"",
            "ownershipType"=>"",
            "accountType"=>"JARI_ACCOUNT",
            "currencyType"=>"",
            "maxLenForNote"=>"200",
            "selectedDeposit"=>$this->account,
            "selectedDepositValueType"=>"sourceDeposit",
            "selectedDepositPinnedDeposit"=>"",
            "selectedDepositIsComboValInStore"=>"false",
            "billType"=>"",
            "fromDateTime"=>$aMonthAgoDate, //"1402/04/17  -  00:00",
            "toDateTime"=>$nowDate,//"1402/05/17  -  23:59",
            "minAmount"=>"",
            "currency"=>"IRR",
            "currencyDefaultFractionDigits"=>"2",
            "maxAmount"=>"",
            "order"=>"DESC",
            "desc"=>"",
            "paymentId"=>"",
            "stmtIdnote1"=>$stmtIdnote1
        ];

        $viewDetailsAccountHtmlReportUrl = 'https://ib.sinabank.ir/webbank/viewAcc/depositShow.action?'.http_build_query($depositShowData);
        $viewDetailsAccountHtmlReport = $this->http->get($viewDetailsAccountHtmlReportUrl,'post','','','');

        $securityError = 'خطر امنیتی';
        if (strpos($viewDetailsAccountHtmlReport, $securityError) !== false) {
            $this->newLog("security Error to load Statements!!", 'securityErrorInStatement');
            return false;
        }

        if($viewDetailsAccountHtmlReport == false){
            return $viewDetailsAccountHtmlReport;
        }
        $this->newLog(convertToString($viewDetailsAccountHtmlReport),"viewDetailsAccountHtmlReport");

        $statements = getDeposits($viewDetailsAccountHtmlReport,$this->user_id ,$this->banking_id);

        $this->newLog('statements:'.var_export($statements,true),"logStatements");
        return $statements;
    }

    public function getBalances()
    {
        $balanceUrl = "https://ib.sinabank.ir/webbank/viewAcc/viewAccAction.action";
        $balanceResponse = $this->http->get($balanceUrl,'get','','','');

        if(strpos($balanceResponse ,"موجودی") == false || strpos($balanceResponse , "مبلغ مسدودی") == false){
            $accounts = getAccountsLinks($balanceResponse);
            foreach($accounts as $account){
                $accountLink = "https://ib.sinabank.ir/webbank/viewAcc/$account";
                $balanceResponse = $this->http->get($accountLink,'get','','','');
                if(strpos($balanceResponse ,$this->account) != false){
                    $this->newLog($balanceResponse,'getBalances2');
                    break;
                }
            }
        }

        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(convertToString($balanceResponse), 'balanceResponse');
        }
        // get balance from html
        $balance = getBalance($balanceResponse,$this->account);
        $this->newLog(json_encode($balance),'balance');

        return $balance;
    }

    function getSigninPage()
    {
        $firstUrl = 'https://ib.sinabank.ir/webbank/login/loginPage.action?ibReq=WEB';
        $firstResponse = $this->http->get($firstUrl,'get','','','');

        $secondUrl = 'https://ib.sinabank.ir/webbank/dispatcherNamespace/dispatcherAction.action?ibReq=WEB';
        $secondResponse = $this->http->get($secondUrl,'get','','','');

        $thirdResponse = $this->http->get($firstUrl,'get','','','');

        return $thirdResponse;

    }

    function getDataFromSigninPage(string $signinPage)
    {
        $loginData = [
            'struts.token.name' => 'loginToken',
            'otpSyncRequired' => 'false',
            'username' => $this->username,
            'password' => $this->password,
            'loginType' => 'STATIC_PASSWORD',
            'isSoundCaptcha' => 'false',
            'soundCaptchaEnable' => 'true',
            'hiddenPass1' => '1',
            'hiddenPass2' => '2',
            'hiddenPass3' => '3',
        ];
        $loginData['loginToken'] = getInputTag($signinPage, '/<input type="hidden" name="loginToken" value=".*/');

        $captchaUrl = 'https://ib.sinabank.ir/webbank/login/captcha.action?isSoundCaptcha=false&r=3192574953940366';
        $captchaRawImage = $this->http->get($captchaUrl,'get','','','');
//        $captchaRawImageLength = strlen($captchaRawImage);
//        $firstStrings = substr($captchaRawImage, 0, 100);

        $loginData['has_captcha'] = ($captchaRawImage != '') ? true : false;
        $loginData['needs_captcha'] = $loginData['has_captcha'];
        if($loginData['has_captcha']){
            writeOnFile($this->captchaFile, $captchaRawImage);
        }
        return $loginData;
    }

    function sendSMSCodeToUser(array $data)
    {
        $textForSms = "لطفا بلیت امنیتی ارسال شده به تلفن همراه ";
        $logoutLink = "/webbank/login/logout.action";
        $SMSUrl = "https://ib.sinabank.ir/webbank/login/login.action?ibReq=WEB&lang=fa";
        $SMSResponse = $this->http->get($SMSUrl,'post','',$data,'');
        if ($this->banking_id == $this->testingBankingId) {
            $this->newLog(var_export($SMSResponse, true), 'sina-autoSigninStep1-sendSMSCodeToUser');
        }
        if (!$SMSResponse) {
            return [
                "message" => "Sending SMS failed !!(No Response)",
                "status" => false
            ];
        }
        if(strpos($SMSResponse,$textForSms) == false){
            $result = [
                "message" => "Sending SMS failed !!",
                "status" => false
            ];
            if(strpos($SMSResponse, $logoutLink) !== false) {
                $result = [
                    "data" => $SMSResponse,
                    "message" => "noNeedSMS",
                    "status" => true
                ];
            }
            return $result;
        }

        return [
            "data" => $SMSResponse,
            "message" => "Sending SMS successfully !!",
            "status" => true
        ];
    }

    public function getCodeFromSMS($messages,$type=1)
    {
        if($type == 1){ // for login
            foreach($messages as $message) {
                if((strpos($message['message'],'بانک سينا') !== false) && (strpos($message['message'],'ورود') !== false)) {
                    preg_match_all('!\d{6}!', $message['message'], $matches);
                    if(isset($matches[0][0])) {
                        return $matches[0][0];
                    }
                }
            }
        }
        else if($type == 2){ // for paya transfer
            foreach($messages as $message) {
                if((strpos($message['message'],'بانک سينا') !== false) && (strpos($message['message'],'پایا') !== false)) {
                    preg_match('!\d{6}!', $message['message'], $matches);
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
        $loginUrl2 = 'https://ib.sinabank.ir/webbank/login/twoPhaseLoginWithTicket.action?ibReq=WEB&lang=fa';
        $loginResponse2 = $this->http->get($loginUrl2,'post','',$twoPhaseData,'');

        $urlCheckUsername = 'https://ib.sinabank.ir/webbank/login/checkUsername.action';
        $checkUsernameResponse = $this->http->get($urlCheckUsername,'get','','','');

        $urlCheckPassword = 'https://ib.sinabank.ir/webbank/login/checkPassword.action';
        $checkPasswordResponse = $this->http->get($urlCheckPassword,'get','','','');

        $urlCompleteLogin = 'https://ib.sinabank.ir/webbank/login/completeLogin.action';
        $completeLoginResponse = $this->http->get($urlCompleteLogin,'get','','','');


        $res = [
            $loginResponse2,
            $checkUsernameResponse,
            $checkPasswordResponse,
            $completeLoginResponse,
        ];

        return true;
    }

    public function getTransferRemainingLimit()
    {
        $newNormalAchUrl = 'https://ib.sinabank.ir/webbank/transfer/newNormalAch.action';
        $newNormalAchUrlResponse = $this->http->get($newNormalAchUrl, 'get', '', '', '');

        $newNormalAchUrlResponse = convertPersianNumberToEnglish($newNormalAchUrlResponse);
        preg_match_all('/<div class="item-field-info">(.*?)<\/div>/s', $newNormalAchUrlResponse, $matches);

        $output = preg_replace( '/[^0-9]/', '', $matches[0]);

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
        $formattedAmount = number_format($amount,0,'.','٫');

        $newNormalAchUrl = "https://ib.sinabank.ir/webbank/transfer/newNormalAch.action";
        $newNormalAchUrlResponse = $this->http->get($newNormalAchUrl, 'get', '', '', '');

        if($newNormalAchUrlResponse == "" || $newNormalAchUrlResponse == null)
        {
            $this->newLog(var_export($newNormalAchUrlResponse, true),'EmptyNewNormalAchUrlResponse');
            return [
                'status' => 0,
                'error' => 'Empty GenerateTicketResponse',
            ];

        }

        $normalAchTransferToken = getInputTag($newNormalAchUrlResponse,'/<input type="hidden" name="normalAchTransferToken" value=".*/');
        $this->newLog(var_export($newNormalAchUrlResponse, true),'newNormalAchUrlResponse');

        $normalAchTransferUrl = "https://ib.sinabank.ir/webbank/transfer/normalAchTransfer.action";
        $normalAchTransferData = [
            "transferType" => "NORMAL_ACH",
            "struts.token.name" => "normalAchTransferToken",
            "normalAchTransferToken" => $normalAchTransferToken,//"E789QL7KHY0NX0TQXLTJ9SDDNSIPAXHU",
            "sourceSaving" => $this->account,
            "sourceSavingValueType" => "sourceDeposit",
            "sourceSavingPinnedDeposit" => "",
            "sourceSavingIsComboValInStore" => "false",
            "destinationIbanNumber" => $iban,
            "destinationIbanNumberValueType" => "",
            "destinationIbanNumberPinnedDeposit" => "",
            "destinationIbanNumberIsComboValInStore" => "false",
            "owner" => "$name $surname",
            "amount" => $formattedAmount,
            "currency" => "",
            "currencyDefaultFractionDigits" => "",
            "reason" => "DRPA",
            "factorNumber" => "",
            "remark" => ""
        ];
        $this->newLog(var_export($normalAchTransferData, true),'normalAchTransferData');

        $newNormalAchUrlResponse = $this->http->get($normalAchTransferUrl, 'post', '', $normalAchTransferData, '');
        $csrfTokenPattern = '/<meta name="CSRF_TOKEN" content=.*>/';
        $csrfToken = getMetaTag($newNormalAchUrlResponse,$csrfTokenPattern);
        if($csrfToken === false){
            $this->newLog("Not found this pattern: $csrfTokenPattern","notFoundThisPattern");
            return false;
        }

        $generateTicketData = [
            "CSRF_TOKEN" => $csrfToken,//"OUd+QzsQ6qTyqILm/eSBOsy0JwngNCcc9J89FfAxqDc=",
            "ticketAmountValue" => $amount,
            "ticketModernServiceType" => "NORMAL_ACH_TRANSFER",
            "ticketParameterResourceType" => "DEPOSIT",
            "ticketParameterResourceValue" => $this->account,
            "ticketParameterDestinationType" => "IBAN",
            "ticketParameterDestinationValue" => $iban,
            "ticketDestinationName" => "$name $surname",
            "ticketAdditionalInfoAmount" => ""
        ];
        $this->newLog(var_export($generateTicketData, true),'generateTicketData');

        $generateTicketUrl = "https://ib.sinabank.ir/webbank/general/generateTicket.action?".http_build_query($generateTicketData);
        $generateTicketResponse = $this->http->get($generateTicketUrl, 'get', '', '', '');

        if($generateTicketResponse == "" || $generateTicketResponse == null)
        {
            $this->newLog(var_export($generateTicketResponse, true),'EmptyGenerateTicketResponse');
            return [
                'status' => 0,
                'error' => 'Empty GenerateTicketResponse',
            ];

        }
        $generateTicketResponseJson = json_decode($generateTicketResponse,true);

        $pattern = '/<input type="hidden" name="normalAchTransferConfirmToken" value="(.*?)">/s';
        $normalAchTransferConfirmToken = getInputTag($newNormalAchUrlResponse,$pattern);
        $this->newLog(var_export($normalAchTransferConfirmToken, true),'normalAchTransferConfirmToken');

        if($generateTicketResponseJson['resultType'] === "success"){
            $data = [
                'iban' => $iban,
                'amount' => $formattedAmount,
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
            $this->newLog("There is not code",'noOTPCode');
            return [
                'status' => 0,
                'error' => 'There is not otp code',
            ];
        }

        if($data === false){
            $this->newLog("There is Data for payaTransferStep2",'noDataForPayaTransferStep2');
            return [
                'status' => 0,
                'error' => "There is Data for payaTransferStep2",
            ];
        }

        $normalAchTransferUrl = "https://ib.sinabank.ir/webbank/transfer/normalAchTransfer.action";
        $newNormalAchUrlData = [
            'struts.token.name' => 'normalAchTransferConfirmToken',
            'normalAchTransferConfirmToken' => $data["normalAchTransferConfirmToken"],//'185BP49QHRXWEUYYK9ZZOE186D9YEJ71',
            "transferType" => "NORMAL_ACH",
            "sourceSaving" => $this->account,
            "destinationIbanNumber" => $data["iban"],
            "owner" => $data['name'] . " " . $data['surname'],
            "amount" => $data['amount'],
            "currency" => "IRR",
            "reason" => "DRPA",
            "factorNumber" => "",
            "remark" => "",
            "hiddenPass1" => "1",
            "hiddenPass2" => "2",
            "hiddenPass3" => "3",
            "ticketRequired" => "true",
            "ticketResendTimerRemaining" => "15",
            "ticket" => $otp,
            "back" => "back",
            "perform" => "ثبت اوليه"
            // "ثبت+اوليه"
            //,"ثبت انتقال وجه",
        ];
        $this->newLog(json_encode($newNormalAchUrlData),'newNormalAchUrlData');

        $newNormalAchUrlResponse = $this->http->get($normalAchTransferUrl, 'post', '', $newNormalAchUrlData, '');

        if($newNormalAchUrlResponse == "" || $newNormalAchUrlResponse == null)
        {
            return [
                'status' => 'unknown',
                'debug' => $newNormalAchUrlResponse."\n\n".$this->http->getVerboseLog(),
            ];
        }

        $invalidOtpText = "بلیت امنیتی نامعتبر است، لطفا بلیت امنیتی جدید تولید کرده و آنرا بدرستی وارد نمایید";
        if(strpos($newNormalAchUrlResponse,$invalidOtpText) !== false)
        {
            $this->newLog($invalidOtpText,'invalidOtpText');
            return [
                'status' => 0,
                'error' => $invalidOtpText,
            ];
        }

        $limitTransactionText = 'مبلغ" بیش از مبلغ تعیین شده سقف روزانه است';
        if(strpos($newNormalAchUrlResponse,$limitTransactionText) !== false)
        {
            $this->newLog($limitTransactionText,'limitTransactionText');
            return [
                'status' => 0,
                'error' => $limitTransactionText,
            ];
        }

        $sameBankText = "شبای واردشده، متعلق به همین بانک است. برای انتقال به این مقصد از انتقال وجه های داخلی استفاده کنید.";
        if(strpos($newNormalAchUrlResponse,$sameBankText) !== false)
        {
            $this->newLog($sameBankText,'sameBankText');
            return [
                'status' => 0,
                'error' => $sameBankText,
            ];
        }

        $technicalErrorText = "انجام این عملیات به دلیل خطای فنی ممکن نیست. می‌توانید دوباره سعی کنید یا اینکه با بخش پشتیبانی تماس بگیرید.";
        if(strpos($newNormalAchUrlResponse,$technicalErrorText) !== false)
        {
            $this->newLog($technicalErrorText,'technicalErrorText');
            return [
                'status' => 0,
                'error' => $technicalErrorText,
            ];
        }

        $successfulText = 'انتقال وجه بین بانکی پایا عادی ثبت شد.';
        if(strpos($newNormalAchUrlResponse,$successfulText) !== false)
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

        return [
            'status' => 'unknown',
            'debug' => $newNormalAchUrlResponse."\n\n".$this->http->getVerboseLog(),
        ];
    }
}
