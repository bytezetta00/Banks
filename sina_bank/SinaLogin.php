<?php

require_once "./global.php";

class SinaLogin{
    // protected string $userName = "meysam8900";
    // protected string $password = "SH@nyal0118DG";
    // protected string $proxy = PROXY;
    protected DOMDocument $domDocument;
    protected array $loginData;
    protected array $loginData2;
    public array $englishNumber;
    public function __construct(
        private string $userName = "hashemi8900",//"soheil89000",//"meysam8900",
        private string $password = "D@nyal0118DGNN",//"D@nyal0118DG@Ss",//"SH@nyal0118DG",
        private string $account = "331-12-4920878-1",//"331-12-4898511-1",//"331-12-4874735-1",
        private string $proxy = PROXY,
        private string $proxyUserPwd = PROXYUSERPWD,
        public array $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
        )    {
            $this->domDocument = new DOMDocument();
            $this->loginData = $this->getLoginData();
            $this->loginData2 = $this->getLoginData2();
            $this->englishNumber = range(0, 9);
    }

    public function login()
    {
        $firstUrl = 'https://ib.sinabank.ir/webbank/login/loginPage.action?ibReq=WEB';
        $this->curlRequest($firstUrl);

        $secondUrl = 'https://ib.sinabank.ir/webbank/dispatcherNamespace/dispatcherAction.action?ibReq=WEB';
        $this->curlRequest($secondUrl);

        $thirdResponse = $this->curlRequest($firstUrl);
        $this->loginData['loginToken'] = $this->getInputTag($thirdResponse["body"], '/<input type="hidden" name="loginToken" value=".*/'); //get current token

        $captchaUrl = 'https://ib.sinabank.ir/webbank/login/captcha.action?isSoundCaptcha=false&r=3192574953940366';
        $captchaResponse = $this->curlRequest($captchaUrl);
        if ($captchaResponse["body"] != '') {
            // save captcha image
            writeOnFile('images/captcha.png', $captchaResponse["body"]);
            $this->loginData['captcha'] = readline('Enter the captcha:');
            var_dump($this->loginData);

            $loginUrl = "https://ib.sinabank.ir/webbank/login/login.action?ibReq=WEB&lang=fa";
            $loginResponse = $this->curlRequest($loginUrl,http_build_query($this->loginData));//
            // $textForSms = "لطفا بلیت امنیتی ارسال شده به تلفن همراه ";
            var_dump($loginResponse["code"]);
            writeOnFile('responses/loginResponse.html',$loginResponse["body"]);

            $this->loginData2["ticketCode"] = readline('Enter the SMS:');
            $this->loginData2["ticketLoginToken"] = $this->getInputTag($loginResponse["body"], '/<input type="hidden" name="ticketLoginToken" value=".*/');
            $this->loginData2["mobileNumber"] = $this->getInputTag($loginResponse["body"], '/<input type="hidden" class="" name="mobileNumber" id="mobileNumber" value=".*/');

            $loginUrl2 = 'https://ib.sinabank.ir/webbank/login/twoPhaseLoginWithTicket.action?ibReq=WEB&lang=fa';
            $this->curlRequest($loginUrl2 ,http_build_query($this->loginData2));

            $urlCheckUsername = 'https://ib.sinabank.ir/webbank/login/checkUsername.action';
            $this->curlRequest($urlCheckUsername);

            $urlCheckPassword = 'https://ib.sinabank.ir/webbank/login/checkPassword.action';
            $this->curlRequest($urlCheckPassword);

            $urlCompleteLogin = 'https://ib.sinabank.ir/webbank/login/completeLogin.action';
            $this->curlRequest($urlCompleteLogin);

            $homePageUrl = 'https://ib.sinabank.ir/webbank/home/homePage.action';
            $homePageResponse = $this->curlRequest($homePageUrl);
            writeOnFile('responses/homePageResponse.html',$homePageResponse["body"]);

            // it shows account balance
            $balanceUrl = "https://ib.sinabank.ir/webbank/viewAcc/viewAccAction.action";
            $balanceResponse = $this->curlRequest($balanceUrl);
            writeOnFile('responses/balanceResponse.html',$balanceResponse["body"]);
            $balance = $this->getBalance($balanceResponse["body"]);
            var_dump($balance);

            // show default statements
            $depositShowUrl = "https://ib.sinabank.ir/webbank/viewAcc/defaultBillList.action?selectedDeposit=$this->account&accountType=JARI_ACCOUNT&currency=IRR";
            var_dump($depositShowUrl);
            $depositShowResponse = $this->curlRequest($depositShowUrl);
           
            writeOnFile('responses/depositShowResponse.html',$depositShowResponse["body"]);
            
            $depositShowToken = $this->getInputTag($depositShowResponse["body"],'/<input type="hidden" name="depositShowToken" value=".*/');
            $stmtIdnote1 = $this->getInputTag($depositShowResponse["body"],'/<input type="hidden" class="" name="stmtIdnote1" id="stmtIdnote1" value=".*/');

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
                "fromDateTime"=>"1402/05/20  -  00:00",
                "toDateTime"=>"1402/06/20  -  23:59",
                "minAmount"=>"",
                "currency"=>"IRR",
                "currencyDefaultFractionDigits"=>"2",
                "maxAmount"=>"",
                "order"=>"DESC",
                "desc"=>"",
                "paymentId"=>"",
                "stmtIdnote1"=>$stmtIdnote1
                // 30719836_1691182177000_331_1
                //"331-12-4898511-1",//"331-12-4874735-1",
            ];
            var_dump($depositShowData);

            // show default statements
            $viewDetailsAccountHtmlReportUrl = 'https://ib.sinabank.ir/webbank/viewAcc/depositShow.action?'.http_build_query($depositShowData);
            $viewDetailsAccountHtmlReportResponse = $this->curlRequest($viewDetailsAccountHtmlReportUrl);
            writeOnFile('responses/viewDetailsAccountHtmlReportResponse.html',$viewDetailsAccountHtmlReportResponse["body"]);
            
            
            return $this->getDeposits($viewDetailsAccountHtmlReportResponse["body"]);
        }
        return "Can not get captcha !!";
    }

    public function curlRequest(string $url, $data = NULL, $headers = [], $proxy = PROXY, $proxyuserpwd = PROXYUSERPWD, $cookieFile = COOKIE_FILE, $userPass = null): array
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

    public function getInputTag(string $html, string $pattern)
    {
        $doc = $this->domDocument;
        preg_match($pattern, $html, $matches);
        $text = "<html><body>
        $matches[0]
        </body></html>";
        $doc->loadHTML($text);

        $result = null;
        if ($doc->getElementsByTagName("input"))
            $result = $doc->getElementsByTagName("input")[0]->getAttribute("value");
        return $result;
    }

    public function getLoginData() :array
    {
        return [
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
    }

    public function getLoginData2() :array
    {
        return [
            "struts.token.name" => "ticketLoginToken",
            "ticketResendTimerRemaining" => -1,
            "hiddenPass1" => 1,
            "hiddenPass2" => 2,
            "hiddenPass3" => 3,
        ];
    }

    public function balanceData()
    {
        return [
            "smartComboType" =>	"DEPOSIT",
            "showContacts" => "false",
            "businessType" => "all",
            "serviceName" => "",
            "currency" => ""
        ];
    }

    public function getBalance(string $html)
    {
        $doc = $this->domDocument;

        preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
        $text = "<html><body>
        $matches[0]
        </body></html>";

        $doc->loadHTML($text);
        $trs = $doc->getElementsByTagName("tr");

        $balance = $this->setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(1)->textContent);
        $availableBalance = $this->setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(2)->textContent);
        $blocked = $this->setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(4)->textContent);

        return [
            'balance' => $balance, 
            'availableBalance' => $availableBalance, 
            'blocked' => $blocked
        ];
    }

    public function getDeposits(string $html)
    {
        $doc = $this->domDocument;
        preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
        $text = "<html><body>
        $matches[0]
        </body></html>";
        $text = $this->convertPersianNumberToEnglish($text);
        $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $trs = $doc->getElementsByTagName("tr");
        for ($i = 2; $trs->count() > $i; $i++) {
            $details = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent; // this is "sharh" it has bunch of data, we need
            $date = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent; // date
            $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent; // deposit 
            $description = $trs->item($i)->getElementsByTagName("td")->item(8)->textContent; // shomareh sanad(document id) & saat(hour-time)

            preg_match_all('!\d*:\d*:\d*!', $description, $matches);
            $hour = (strpos($description, 'ساعت') !== false) ? $matches[0][0] : "00:00:00";
            $datetime = "$date $hour";
            $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime);

            preg_match_all('! \d{8}!', $description, $matches);
            $sanad = (strpos($description, 'سند') !== false) ? $matches[0][0] : null;

            preg_match_all('! \d{12} !', $details, $matches);
            $erja = (strpos($details, 'ش م') !== false) ? $matches[0][0] : null;

            preg_match_all('! \d{16}!', $details, $matches);
            $cardNumber = (strpos($details, 'از ک') !== false) ? $matches[0][0] : null;

            if (str_contains($deposit, "-")) {
                continue;
            } else {
                $result[] = [
                    'amount' => trim($deposit),
                    'erja' => trim($erja),
                    'peygiri' => trim($erja),
                    'serial' => trim($sanad),
                    'card_number' => trim($cardNumber),
                    'datetime' => $datetime,
                    'bigint_datetime' => $bigintDatetime,
                ];
            }
        }
        return $result;
    }

    public function setPersianFormatForBalance(string $text)
    {
        $encodedText = mb_convert_encoding(
            $text,
            'ISO-8859-1',
            'UTF-8');
        return trim(
            str_replace(
                $this->persianNumber,$this->englishNumber,$encodedText
            ));
    }

    public function convertPersianNumberToEnglish(string $text)
    {
        return str_replace($this->persianNumber, $this->englishNumber, $text);
    }
}

$sinaLogin = new SinaLogin();
var_dump($sinaLogin->login());