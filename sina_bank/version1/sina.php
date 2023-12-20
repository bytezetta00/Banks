<?php
load('http');
load('date');

class Sina extends banking{

    private $account;
    private $username;
    private $password;
    private $user_id;
    private $banking_id;
    private $http;
    private $needs_login_task = true;
    private $bankName = 'sina';
    private $cookieFile;
    private $captchaFile;

    public function __construct(array $data,$user_id ,$banking_id)
    {
        $GLOBALS['account'] = $this->account = $data['account']; //'4001002408872'
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

        if(strpos($homePage, $this->account) !== false) {
            $balance = $this->getBalances();
            $GLOBALS['balance'] = str_replace(',','',$balance);
            $GLOBALS['deposits'] = $this->logStatements();
            return true;
        } else {
            return false;
        }
    }

    public function autoSigninStep1()
    {
        $signinPage = $this->getSigninPage();
        if($signinPage == null || $signinPage == "" || $signinPage == false){
            return [
                "message" => "Signin page didn't load currectly !!",
                "status" => false
            ];
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

        $this->loginData2["ticketLoginToken"] = $this->getInputTag($sendSMSResponse["body"], '/<input type="hidden" name="ticketLoginToken" value=".*/');
        $this->loginData2["mobileNumber"] = $this->getInputTag($sendSMSResponse["body"], '/<input type="hidden" class="" name="mobileNumber" id="mobileNumber" value=".*/');

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

    private function logStatements($datetime='null', $amount='null')
    {
        $depositShowUrl = "https://ib.sinabank.ir/webbank/viewAcc/defaultBillList.action?selectedDeposit=$this->account&accountType=JARI_ACCOUNT&currency=IRR";
        $depositShowHtml = $this->http->get($depositShowUrl,'get','','','');
        if($depositShowHtml == false){
            return false;
        }

        $depositShowToken = $this->getInputTag($depositShowHtml["body"],'/<input type="hidden" name="depositShowToken" value=".*/');
        $stmtIdnote1 = $this->getInputTag($depositShowHtml["body"],'/<input type="hidden" class="" name="stmtIdnote1" id="stmtIdnote1" value=".*/');

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
            "fromDateTime"=>"1402/04/17  -  00:00",
            "toDateTime"=>"1402/05/17  -  23:59",
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
        $viewDetailsAccountHtmlReport = $this->http->get($depositShowUrl,'post','',$viewDetailsAccountHtmlReportUrl,'');
        if($viewDetailsAccountHtmlReport == false){
            return $viewDetailsAccountHtmlReport;
        }
        return getDeposits($viewDetailsAccountHtmlReport,$this->user_id ,$this->banking_id);
    }

    private function getBalances()
    {
        $balanceUrl = "https://ib.sinabank.ir/webbank/viewAcc/viewDetails.action?accountType=JARI_ACCOUNT";
        $balanceData = [
            "smartComboType" =>	"DEPOSIT",
            "showContacts" => "false",
            "businessType" => "all",
            "serviceName" => "",
            "currency" => ""
        ];
        $balanceResponse = $this->http->get($balanceUrl,'post','',$balanceData,'');

        // get balance from html 
        return getBalance($balanceResponse,$this->account);
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
            'username' => $this->userName,
            'password' => $this->password,
            'loginType' => 'STATIC_PASSWORD',
            'isSoundCaptcha' => 'false',
            'soundCaptchaEnable' => 'true',
            'hiddenPass1' => '1',
            'hiddenPass2' => '2',
            'hiddenPass3' => '3',
        ];
        $this->loginData['loginToken'] = $this->getInputTag($signinPage, '/<input type="hidden" name="loginToken" value=".*/'); 

        $captchaUrl = 'https://ib.sinabank.ir/webbank/login/captcha.action?isSoundCaptcha=false&r=3192574953940366';
        $captchaRawImage = $this->http->get($captchaUrl,'get','','','');
        $loginData['has_captcha'] = ($captchaRawImage != '') ? true : false;
        $loginData['needs_captcha'] = $loginData['has_captcha'];
        if($loginData['has_captcha']){
            writeOnFile($this->captchaFile, $captchaRawImage);
        }
        return $loginData;
    }

    function sendSMSCodeToUser(array $data)
    {
        $SMSUrl = "https://ib.sinabank.ir/webbank/login/login.action?ibReq=WEB&lang=fa";
        $SMSResponse = $this->http->get($SMSUrl,'post','',$data,'');
        if (!$SMSResponse) {
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
                if((strpos($message['message'],'بانک سینا') !== false) || (strpos($message['message'],'ورود') !== false)) {
                    preg_match_all('!\d{6}!', $message['message'], $matches);
                    if(isset($matches[0][0])) {
                        return $matches[0][0];
                    }
                }
            }
        }
        else if($type == 2){ // for paya transfer
            foreach($messages as $message) {
                if((strpos($message['message'],'بانک سینا') !== false) || (strpos($message['message'],'پایا') !== false)) {
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

        return [
            $loginResponse2,
            $checkUsernameResponse,
            $checkPasswordResponse,
            $completeLoginResponse,
        ];

    }
}