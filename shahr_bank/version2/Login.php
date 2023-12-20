<?php 

class Login
{
    protected $urlHomePage;
    protected $firstUrl;
    protected $secondUrl;
    public function __construct(
        protected DOMDocument $domDocument,
        protected Data $data
    ) {
        $this->urlHomePage = 'https://ebank.shahr-bank.ir/ebank/home/homePage.action';
        $this->firstUrl = 'https://ebank.shahr-bank.ir/ebank/login/loginPage.action?ibReq=WEB';
        $this->secondUrl = 'https://ebank.shahr-bank.ir/ebank/dispatcherNamespace/dispatcherAction.action?ibReq=WEB';

    }

    public function loadLoginPage()
    {
        // $queryParams = getQueryParams();
        // $loginData = getLoginData();
        return $this->data->loginData;
    }

    public function checkLogin()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY,'ctr-2-1m.geosurf.io:8000');
        curl_setopt($ch, CURLOPT_PROXYUSERPWD,'630386+IR+630386-750244:e9fdbb701');
        curl_setopt($ch, CURLOPT_URL, $this->urlHomePage);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"]);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['body' => $result,'status_code' => $code];
    }

    public function firstLoginCurl()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY,'ctr-2-1m.geosurf.io:8000');
        curl_setopt($ch, CURLOPT_PROXYUSERPWD,'630386+IR+630386-750244:e9fdbb701');
        curl_setopt($ch, CURLOPT_URL, $this->firstUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"]);
        curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        $firstResponse = curl_exec($ch);
        return [
            'firstResponse' => $firstResponse,
            'ch' => $ch,
        ];
    }

    public function secondLoginCurl($ch)
    {
        curl_setopt($ch, CURLOPT_URL, $this->secondUrl);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        $secondResponse = curl_exec($ch);
        return [
            'secondResponse' => $secondResponse,
            'ch' => $ch,
        ];
    }

    public function thirdLoginCurl($ch)
    {
        curl_setopt($ch, CURLOPT_URL, $this->firstUrl);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        $thirdResponse = curl_exec($ch); // now whole html loaded
        return [
            'thirdResponse' => $thirdResponse,
            'ch' => $ch,        
        ];
    }

    public function getCaptchaCurl($ch)
    {
        $captchaUrl = 'https://ebank.shahr-bank.ir/ebank/login/captcha.action?isSoundCaptcha=false';
        curl_setopt($ch, CURLOPT_URL, $captchaUrl);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        $captchaRawImage = curl_exec($ch);
        return [
            'captchaRawImage' => $captchaRawImage,
            'ch' => $ch,
        ];
    }

}