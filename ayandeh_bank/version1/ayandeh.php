<?php
load('http');

class ayandeh extends banking{

    private $account;
    private $username;
    private $password;
    private $user_id;
    private $banking_id;
    private $http;
    private $needs_login_task = true;
    private $bankName = 'ayandeh';
    private $cookieFile;
    private $captchaFile;
    private $codeUrl;

    public function __construct(array $data,$user_id ,$banking_id)
    {
        $GLOBALS['account'] = $this->account = $data['account'];
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->user_id = $user_id;
        $this->banking_id = $banking_id;
        $this->cookieFile = COOKIE_PATH . "$this->bankName-$this->banking_id.txt";
        $this->captchaFile = UPLOAD_PATH . "$this->bankName-captcha-$this->username.jpg";
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
        $homeUrl = "https://old.abplus.ir/dashboard";

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
        $abplusResponse = $this->http->get('https://old.abplus.ir','get','','','');
        if($abplusResponse == null || $abplusResponse == "" || $abplusResponse == false){
            error_log('Error in the server connection!');
            return false;
        }
        $authResponse = $this->http->get('https://id.ba24.ir/auth?response_type=code&scope=openid&client_id=pishkhan2&redirect_url=https://old.abplus.ir/auth','get','','','');
        $authResponse2 = $this->http->get('https://id.ba24.ir/auth/?response_type=code&scope=openid&client_id=pishkhan2&redirect_url=https://old.abplus.ir/auth','get','','','');
        $ba24Response = $this->http->get('https://id.ba24.ir/','get','','','');
        $meResponse = $this->http->get('https://id.ba24.ir/core/me','get','','','');
        $captchaResponse = $this->http->get('https://id.ba24.ir/core/inquiryCaptcha','get','','','');
        $captchaCode = getCaptchaCode($captchaResponse,$this->captchaFile);
        $otpData = [
            "captcha" => $captchaCode,
            "nid" => USER_NAME
        ];
        $otpResponse = $this->sendSMSCodeToUser($otpData);
        
        return getAuthenticateData($captchaCode);
    }

    public function autoSigninStep2($data,$otp)
    {
        $data["otp"] = $otp;
        if($this->twoPhaseLogin($data)) {
            return true;
        } else {
            return false;
        }
    }

    private function logStatements($datetime='null', $amount='null')
    {
        $statementkarizUrl = 'https://old.abplus.ir/panel/kariz/statementkariz';
        $statementkarizResponse = $this->http->get($statementkarizUrl,'get','','','');
        if($statementkarizResponse == false){
            error_log('Error in the report URL!');
            return false;
        }
        $statementkarizData = getStatementkarizData($statementkarizResponse,$this->account);
        $statementkarizPostResponse = $this->http->get($statementkarizUrl,'post','',$statementkarizData,'');
        if($statementkarizPostResponse == false){
            error_log('Error in the report URL!');
            return false;
        }
        return getDeposit($statementkarizPostResponse,$this->user_id, $this->banking_id);
    }

    private function getBalances()
    {
        if($this->codeUrl != false || $this->codeUrl != null){
            $authOldCodeResponse = $this->http->get($this->codeUrl,'get','','','');
            if($authOldCodeResponse == false){
                error_log('Error in the redirect URL(code)!');
                return false;
            }
            $csrfDashboardPattern = '/<input type="hidden" name="csrf" id="csrf" value=".*/';
            $csrfDashboard = getInputTag($authOldCodeResponse, $csrfDashboardPattern);
            [
                "Accept: */*",
                "Content-type: application/json",
                'X-Requested-With: XMLHttpRequest',
                "X-CSRF-TOKEN: $csrfDashboard",
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
            ];
            $accountsStatsResponse = $this->http->get("https://old.abplus.ir/panel/pishkhan/accountsStats?src=3",'get','','','');
            if($accountsStatsResponse == false){
                error_log('Error in the balance URL!');
                return false;
            }
            return getBalance($accountsStatsResponse, $this->account);
        }
        error_log('Error in getting redirect URL!');
        return false;
    }

    function sendSMSCodeToUser(array $data)
    {
        $otpResponse = $this->http->get('https://id.ba24.ir/core/sendOtp','post','',$data,'');
        if (!$otpResponse || $otpResponse == false) {
            error_log('Error in sending sms to the user!');
            return [
                "message" => "Sending SMS failed !!",
                "status" => false
            ];
        }
        return [
            "data" => $otpResponse,
            "message" => "Sending SMS successfully !!",
            "status" => true
        ];
    }

    public function getCodeFromSMS($messages,$type=1)
    {
        if($type == 1){ // for login
            foreach($messages as $message) {
                if((strpos($message['message'],'بانک آینده') !== false) && (strpos($message['message'],'ورود') !== false)) {
                    preg_match_all('!\d{5}!', $message['message'], $matches);
                    if(isset($matches[0][0])) {
                        return $matches[0][0];
                    }
                }
            }
        }
        else if($type == 2){ // for paya transfer
            foreach($messages as $message) {
                if((strpos($message['message'],'بانک آینده') !== false) && (strpos($message['message'],'پایا') !== false)) {
                    preg_match_all('!\d{5}!', $message['message'], $matches);
                    if(isset($matches[0][0])) {
                        return $matches[0][0];
                    }
                }
            }
        }
        else{
            // undefined type
            error_log('undefined type in the SMS!');
            return false;
        }

        return false;
    }

    public function twoPhaseLogin(array $twoPhaseData)
    {
        $authenticateResponse = $this->http->get('https://id.ba24.ir/core/authenticate','post','',json_encode($twoPhaseData),'');
         
        $this->codeUrl = json_decode($authenticateResponse)->redirect_url ?? false;
        if ($this->codeUrl == false) {
            error_log("There is not the redirect url: $authenticateResponse");
            return false;
        }
        return $this->http->get($this->codeUrl,'get','','','');
    }
}
