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
    }

    public function setProxy($config) {
        setBankingProxy($config, $this->bankName, $this->http);
    }

    public function logout()
    {
        unlink($this->cookieFile);
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
        if($signinPage == null || $signinPage == "" || $signinPage == false){
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

        if($sendSMSResponse["status"] == false){
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

    public function autoSigninStep2($data,$otp)
    {
        $data["ticketCode"] = $otp;
        if($this->twoPhaseLogin($data)) {
            return true;
        } else {
            return false;
        }
    }

    public function logStatements($datetime='null', $amount='null')
    {
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
        if($viewDetailsAccountHtmlReport == false){
            return $viewDetailsAccountHtmlReport;
        }
        $statements = getDeposits($viewDetailsAccountHtmlReport,$this->user_id ,$this->banking_id);

        //newLog('statements:'.var_export($statements,true),"logStatements");
        return $statements;
    }

    public function getBalances()
    {
        $balanceUrl = "https://ib.sinabank.ir/webbank/viewAcc/viewAccAction.action";
        $balanceResponse = $this->http->get($balanceUrl,'get','','','');

        // get balance from html
        $balance = getBalance($balanceResponse,$this->account);
        newLog('balance:'.convertToString($balance),"Amir-sina-balance");
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
        $captchaRawImageLength = strlen($captchaRawImage);
        $firstStrings = substr($captchaRawImage, 0, 100);

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
        $SMSUrl = "https://ib.sinabank.ir/webbank/login/login.action?ibReq=WEB&lang=fa";
        $SMSResponse = $this->http->get($SMSUrl,'post','',$data,'');
        //newLog(var_export($SMSResponse,true),'sina-autoSigninStep1-sendSMSCodeToUser');
        if (!$SMSResponse) {
            return [
                "message" => "Sending SMS failed !!(No Response)",
                "status" => false
            ];
        }
        if(strpos($SMSResponse,$textForSms) == false){
            return [
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
                    preg_match_all('!\d{6}!', $message['message'], $matches);
                    if(isset($matches[0][0])) {
                        return $matches[0][0];
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
}
