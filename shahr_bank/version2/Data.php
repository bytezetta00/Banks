<?php

class Data
{

    public $queryParams;
    public $loginData;
    public $loginData2;
    public $balanceData;
    public function __construct()
    {
        $this->queryParams = $this->getQueryParams();
        $this->loginData = $this->getLoginData();
        $this->loginData2 = $this->getLoginData2();
        $this->balanceData = $this->getBalanceData();
    }

    public function getQueryParams()
    {
        return [
            'ibReq' => 'WEB',
            'lang' => 'fa',
        ];
    }

    public function getLoginData(): array
    {
        return [
            'username' => 'behnam8900',
            'password' => 'D@nyal0118DG@Ss',
            'loginType' => 'STATIC_PASSWORD',
            'isSoundCaptcha' => 'false',
            'otpSyncRequired' => 'false',
            'soundCaptchaEnable' => 'true',
            'struts.token.name' => 'loginToken',
            'hiddenPass1' => '1',
            'hiddenPass2' => '2',
            'hiddenPass3' => '3',
        ];
    }

    public function getLoginData2(): array
    {
        return [
            "struts.token.name" => "ticketLoginToken",
            "ticketResendTimerRemaining" => -1,
            "hiddenPass1" => 1,
            "hiddenPass2" => 2,
            "hiddenPass3" => 3,
        ];
    }

    public function getBalanceData(): array
    {
        return [
            'struts.token.name' => "depositShowToken",
            'advancedSearch' => true,
            'maxLenForNote' => '200',
            'selectedDeposit' => '4001002408872',
            'selectedDepositValueType' => 'sourceDeposit',
            'selectedDepositIsComboValInStore' => false,
            'fromDateTime' => '1402/01/26  -  00:00',
            'toDateTime' => '1402/02/25  -  11:52',
            'order' => 'DESC',
        ];
    }

    public function getInputTag(string $html, DOMDocument $doc, string $pattern)
    {
        preg_match($pattern, $html, $matches);
        $text = "<html><body>
        $matches[0]
        </body></html>";
        $doc->loadHTML($text);
        
        return ($doc->getElementsByTagName("input")) ? $doc->getElementsByTagName("input")[0]->getAttribute("value") : null;
    }
}