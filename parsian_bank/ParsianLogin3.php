<?php

require_once "./global.php";

class ParsianLogin
{
    protected DOMDocument $domDocument;
    protected array $loginData;
    protected array $loginData2;
    public array $englishNumber;
    public function __construct(
        private string $userName = "0019209053",//"6539486431",//"2741558191",//"0010517881",
        //"meysam8900",
        private string $password = "Hedie@1375",//"Amir@1362m@Ss",//"D@nyal0118DGk",//"M10510568m@Kk",
        //"SH@nyal0118DG",
        private string $account = "47001499225602",//"47001485069601",//"30101927557601",//"30101790267603",
        //"47001427876609",
        private string $proxy = PROXY,
        private string $proxyUserPwd = PROXYUSERPWD,
        public array $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
    ) {
        $this->domDocument = new DOMDocument();
        $this->loginData = $this->getLoginData();
        $this->loginData2 = $this->getLoginData2();
        $this->englishNumber = range(0, 9);
    }

    public function login()
    {
        $date = date('Y-m-d h:i:s.000', time());
        $previousDate = date('Y-m-d h:i:s.000', strtotime($date . ' -1 months'));
        //return $previousDate;
        $currentDate = str_replace(" ", "T", $date);
        $previousDate = str_replace(" ", "T", $previousDate);
        $fullCurrentDate = $currentDate . "Z";
        $fullPreviousDate = $previousDate . "Z";

        $loginHtmlUrl = "https://ipb.parsian-bank.ir/login.html";
        $loginHtmlResponse = $this->curlRequest($loginHtmlUrl);
        writeOnFile('responses/loginHtmlResponse.html', $loginHtmlResponse["body"]);

        $vendorsVersionUrl = "https://ipb.parsian-bank.ir/vendors/version";
        $vendorsVersionResponse = $this->curlRequest($vendorsVersionUrl);
        writeOnFile("responses/vendorsVersionResponse.html", $vendorsVersionResponse["body"]);

        $forLoginUrl = "https://ipb.parsian-bank.ir/vendors/captcha/forLogin";
        $forLoginResponse = $this->curlRequest($forLoginUrl);
        writeOnFile('images/captcha.png', $forLoginResponse["body"]);

        //POST
        $loginUrl = "https://ipb.parsian-bank.ir/login";
        $this->loginData['captcha'] = readline('Enter the captcha:');

//        challengeKey=&langKey=fa&browserMode=public&otpInProgress=false&currentStep=1&pib_username=6539486431&pib_password=Amir%401362m%40Ss&passwordType=S&captcha=h88ee
        $loginResponse = $this->curlRequest($loginUrl,$this->loginData);
        writeOnFile("responses/loginResponse.html", $loginResponse["body"]);
        // if loginResponse == 1002 captcha wrong
//        POST
//	https://ipb.parsian-bank.ir/login

//        challengeKey=cd2a6958-b9ff-45df-af7e-1e16629&langKey=fa&browserMode=public&otpInProgress=true&currentStep=2&pib_username=6539486431&pib_password=Amir%401362m%40Ss&passwordType=S&otpPassword=15720&captcha=h88ee

        $this->loginData2['challengeKey'] = $loginResponse["body"];
        $this->loginData2['otpPassword'] = readline('Enter the SMS:');

        var_dump($this->loginData2);

        $login2Response = $this->curlRequest($loginUrl,$this->loginData2);
        writeOnFile("responses/login2Response.html", $login2Response["body"]);

        //home page url
        $homeUrl = "https://ipb.parsian-bank.ir/";
        $homeResponse = $this->curlRequest($homeUrl);
        writeOnFile('responses/homeResponse.html', $homeResponse["body"]);

//        https://ipb.parsian-bank.ir/version.hash-vOOci3BJIv0

//        https://ipb.parsian-bank.ir/app/services/operations/DashboardOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/controllers/DashboardController.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/AutoNormalTransferReportOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/ActiveChequeBookletsOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/NormalFundTransferOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/StatementOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/TransferredOtherBankChequesStatusOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/CardToCardFundTransferOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/Utils.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/ChargeServiceOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/AchFundTransferOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/RtgsFundTransferOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/CharityOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/CardlessIssueVoucherOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/ChangeCardPasswordOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/DeactivationCardPasswordOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/BlockSingularCardOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/BillPaymentOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/services/operations/ChangeLoginPasswordOperationsService.ts-vziwmZfmIX7
//        https://ipb.parsian-bank.ir/app/view/Dashboard.html
//        https://ipb.parsian-bank.ir/account/options //POST


        $getAllAccountsUrl = "https://ipb.parsian-bank.ir/account/getAllAccounts"; //POST //data json {"currency":"IRR"}
        $getAllAccountsData = [
            "currency" => "IRR"
        ];
        $getAllAccountsResponse = $this->curlRequest($getAllAccountsUrl,json_encode($getAllAccountsData),[
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ]);
        writeOnFile('responses/getAllAccountsResponse.html', $getAllAccountsResponse["body"]);

        if($getAllAccountsResponse["body"] != null && $getAllAccountsResponse["body"] != "")
        {
            $getAllAccountsJson = json_decode($getAllAccountsResponse["body"]);
            if((json_last_error() === JSON_ERROR_NONE))
            {
                $accounts = $getAllAccountsJson->allAccountList;
                $balance = [];
                foreach ($accounts as $account){
                    if($account->depositNumber == $this->account){
                        $balance['balance'] = (int) $account->balance;
                        $balance['blockedAmount'] = (int) $account->balance - (int) $account->availableBalance;
                    }
                }
                var_dump($balance);
            }
            else{
                var_dump("Invalid Json In Balance.");
            }
        }else{
            var_dump("There is an empty response !");
        }

//        $getOpenTermAccounts = "https://ipb.parsian-bank.ir/account/getOpenTermAccounts";
////        {"ownerStatuses":["DEPOSIT_OWNER","OWNER_OF_DEPOSIT_AND_SIGNATURE","SIGNATURE_OWNER","BROKER"],"accountStatus":"OPEN"}
//        $getOpenTermAccountsData = [
//            "ownerStatuses" => ["DEPOSIT_OWNER","OWNER_OF_DEPOSIT_AND_SIGNATURE","SIGNATURE_OWNER","BROKER"],
//            "accountStatus" => "OPEN"
//        ];
//        $getOpenTermAccountsResponse = $this->curlRequest($getOpenTermAccounts,json_encode($getOpenTermAccountsData),[
//            "Accept: */*",
//            'Content-Type:application/json',
//            'X-KL-ksospc-Ajax-Request:Ajax_Request'
//        ]);
//        writeOnFile('responses/getOpenTermAccountsResponse.html', $getOpenTermAccountsResponse["body"]);

        $statementUrl = "https://ipb.parsian-bank.ir/account/statement";
//        $statementData = [
//            "accountNumber" => $this->account,
//            "orderType" => 2,
//            "fromDate" => "1704486600565",
//            "length" => null
//        ];
        $statementData = [
            "accountNumber" => $this->account,
            "fromDate" => "2023-12-19T20:00:00.000Z",// 1 month before
            "toDate" => "2024-01-06T09:29:13.896Z", // current month
        ];
        $statementResponse = $this->curlRequest($statementUrl,json_encode($statementData),[
            "Accept: */*",
            'Content-Type:application/json',
            'X-KL-ksospc-Ajax-Request:Ajax_Request'
        ]);
        writeOnFile('responses/statementResponse.html', $statementResponse["body"]);

//        {"accountNumber":"47001499225602","orderType":2,"fromDate":1704486600565,"length":null}
        //{"accountNumber":"47001499225602","fromDate":"2023-12-21T20:00:00.000Z","toDate":"2024-01-06T09:29:13.896Z"}
//        {"totalRecord":0,"accountNumber":"47001499225602","rowDtoList":[]}


        //cd2a6958-b9ff-45df-af7e-1e16629
        return true;



        $randomForHash = $this->makeid(10);
        $versionUrl = "https://ipb.parsian-bank.ir/version.hash-v$randomForHash";//GKr5T8hr3q
        echo $versionUrl;
        // cdFzLiRuYR  //ziwmZfmIX7
        $versionResponse = $this->curlRequest($versionUrl
        ,[],[
            "Accept: */*", 
            "Content-Type:application/octet-stream",//;charset=UTF-8
            "Accept-Encoding:gzip, deflate, br",
            "Accept-Language:en-US,en;q=0.5",
            "Cache-Control:no-cache",
            "Connection:keep-alive",
            "Host:ipb.parsian-bank.ir",
            "Pragma:no-cache",
            "Referer:https://ipb.parsian-bank.ir/",
            "Sec-Fetch-Dest:empty",
            "Sec-Fetch-Mode:cors",
            "Sec-Fetch-Site:same-origin",
            // "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0",
            "X-Requested-With:XMLHttpRequest",
        ]
    );
        writeOnFile('responses/versionResponse.html', $versionResponse["body"]);
        echo " --|   |-- ";
        echo base64_decode($versionResponse["body"]);
        echo " --|   |-- ".PHP_EOL;
        echo $versionResponse["body"];
        $currentVersion = $versionResponse["body"];
        $getAllPersianTranslationsUrl = "https://ipb.parsian-bank.ir/wb/translations/getAllPersianTranslations";
        $getAllPersianTranslationsResponse = $this->curlRequest($getAllPersianTranslationsUrl);
        writeOnFile('responses/getAllPersianTranslationsResponse.html', $getAllPersianTranslationsResponse["body"]);

        $dashboardUrl = "https://ipb.parsian-bank.ir/app/view/Dashboard.html";
        $dashboardResponse = $this->curlRequest($dashboardUrl);
        writeOnFile('responses/dashboardResponse.html', $dashboardResponse["body"]);

        $fileServiceUrl = "https://ipb.parsian-bank.ir/app/services/FileService.ts-v$currentVersion";
        $fileServiceResponse = $this->curlRequest($fileServiceUrl);
        writeOnFile('responses/fileServiceResponse.html', $fileServiceResponse["body"]);

        $uIUtilsUrl = "https://ipb.parsian-bank.ir/app/services/UIUtils.ts-v$currentVersion";
        $uIUtilsResponse = $this->curlRequest($uIUtilsUrl);
        writeOnFile('responses/uIUtilsResponse.html', $uIUtilsResponse["body"]);

        $reportUtilsTsUrl = "https://ipb.parsian-bank.ir/app/services/ReportUtils.ts-v$currentVersion";
        $reportUtilsTsResponse = $this->curlRequest($reportUtilsTsUrl);
        writeOnFile('responses/reportUtilsTsResponse.html', $reportUtilsTsResponse["body"]);

        $achTransferReportOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/AchTransferReportOperationsService.ts-v$currentVersion";
        $achTransferReportOperationsServiceResponse = $this->curlRequest($achTransferReportOperationsServiceUrl);
        writeOnFile('responses/achTransferReportOperationsServiceResponse.html', $achTransferReportOperationsServiceResponse["body"]);

        $paymentByReferenceCodeOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/PaymentByReferenceCodeOperationsService.ts-v$currentVersion";
        $paymentByReferenceCodeOperationsServiceResponse = $this->curlRequest($paymentByReferenceCodeOperationsServiceUrl);
        writeOnFile('responses/paymentByReferenceCodeOperationsServiceResponse.html', $paymentByReferenceCodeOperationsServiceResponse["body"]);

        $accountBlockReasonsOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/AccountBlockReasonsOperationsService.ts-v$currentVersion";
        $accountBlockReasonsOperationsServiceResponse = $this->curlRequest($accountBlockReasonsOperationsServiceUrl);
        writeOnFile('responses/accountBlockReasonsOperationsServiceResponse.html', $accountBlockReasonsOperationsServiceResponse["body"]);

        $averageAccountBalanceOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/AverageAccountBalanceOperationsService.ts-v$currentVersion";
        $averageAccountBalanceOperationsServiceResponse = $this->curlRequest($averageAccountBalanceOperationsServiceUrl);
        writeOnFile('responses/averageAccountBalanceOperationsServiceResponse.html', $averageAccountBalanceOperationsServiceResponse["body"]);

        $getAccountSignatureOwnerStatusesUrl = "https://ipb.parsian-bank.ir/account/getAccountSignatureOwnerStatuses";
        $getAccountSignatureOwnerStatusesResponse = $this->curlRequest($getAccountSignatureOwnerStatusesUrl);
        writeOnFile('responses/getAccountSignatureOwnerStatusesResponse.html', $getAccountSignatureOwnerStatusesResponse["body"]);

        $getOpenTermAccountsData = [
            "ownerStatuses" => '',//["DEPOSIT_OWNER", "OWNER_OF_DEPOSIT_AND_SIGNATURE", "SIGNATURE_OWNER", "BROKER"],
            "accountStatus" => "OPEN",
            "alias" => ""
        ];
        $getOpenTermAccountsDataJson = json_encode($getOpenTermAccountsData);
        var_dump($getOpenTermAccountsDataJson);
        $getOpenTermAccountsUrl = "https://ipb.parsian-bank.ir/account/getOpenTermAccounts";
    //     $getOpenTermAccountsResponse = $this->curlRequest($getOpenTermAccountsUrl, $getOpenTermAccountsDataJson,
    //     [
    //         "Accept:*/*",
    //         "Content-Type:application/json",// application/x-www-form-urlencoded
    //         "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/116.0",
    //     ]
    // );
        $accountStatementData = [
            "accountNumber" => $this->account,
            "orderType" => 2,
            "fromDate" => 1701462600295,
            "length" => null
        ];
        $accountStatementUrl = "https://ipb.parsian-bank.ir/account/statement";
        $accountStatementResponse = $this->curlRequest(
            $accountStatementUrl,
            json_encode($accountStatementData),
            [
                "Accept:*/*",
                "Content-Type:application/json",// application/x-www-form-urlencoded
                // "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
                // "Accept-Encoding:gzip, deflate, br",
                // "Accept-Language:en-US,en;q=0.5",
                // "browser-mode:public",
                "Cache-Control:no-cache",
                "Connection:keep-alive",
                // "Content-Length:219",
                "Host:ipb.parsian-bank.ir",
                "Origin:https://ipb.parsian-bank.ir",
                "Pragma:no-cache",
                "Referer:https://ipb.parsian-bank.ir/",
                "Sec-Fetch-Dest:empty",
                "Sec-Fetch-Mode:cors",
                "Sec-Fetch-Site:same-origin",
                "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/116.0",
                // "X-Requested-With:XMLHttpRequest",
                "X-KL-ksospc-Ajax-Request:Ajax_Request"
            ]
        );
        writeOnFile('responses/accountStatementResponse.html', $accountStatementResponse["body"]);
        var_dump($accountStatementResponse);

        $statementData = [
            "accountNumber" => $this->account,
            "fromDate" => "2023-11-03T20:00:00.000Z",
            "toDate" => "2023-12-03T07:25:58.827Z"
        ];
        // {"accountNumber":"47001427876609","orderType":2,"fromDate":1689796800000,"length":null,"fromDateTime":"","toDateTime":"","chequeSerial":"","receiptNo":"","description":"","customerDesc":"","fromAmount":"","toAmount":"","toDate":1692561600000}
        $statementUrl = "https://ipb.parsian-bank.ir/account/statement";
        $statementResponse = $this->curlRequest(
            $statementUrl,
            json_encode($statementData),
            [
                "Accept:*/*",
                "Content-Type:application/json",// application/x-www-form-urlencoded
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
            ]
        );
        writeOnFile('responses/statement.html', $statementResponse["body"]);

        $accountUrl = "https://ipb.parsian-bank.ir/account/getOpenTermAccounts";
        $accountData = [
            "ownerStatuses" => ["DEPOSIT_OWNER", "OWNER_OF_DEPOSIT_AND_SIGNATURE", "SIGNATURE_OWNER", "BROKER"],
            "accountStatus" => "OPEN"
        ];
        $accountResponse = $this->curlRequest(
            $accountUrl,
            json_encode($accountData),
            [
                "Accept:*/*",
                "Content-Type:application/json",
                // application/x-www-form-urlencoded
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
                // "X-Requested-With:XMLHttpRequest",
            ]
        );
        writeOnFile('responses/accountResponse.html', $accountResponse["body"]);

        //https://ipb.parsian-bank.ir/account/statement //for statement POST
        // {"accountNumber":"47001427876609","fromDate":"2023-07-22T20:00:00.000Z","toDate":"2023-08-15T07:25:58.827Z"} //json
        // https://ipb.parsian-bank.ir/account/getOpenTermAccounts //for balance POST 
        // {"ownerStatuses":["DEPOSIT_OWNER","OWNER_OF_DEPOSIT_AND_SIGNATURE","SIGNATURE_OWNER","BROKER"],"accountStatus":"OPEN"}
        return strlen($accountResponse["body"]);
    }

    public function curlRequest(string $url, $data = NULL, $headers = [], $proxy = PROXY, $proxyuserpwd = PROXYUSERPWD, $cookieFile = COOKIE_FILE, $userPass = null)
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

    public function getLoginData()
    {
        return [
            "challengeKey" => "",
            "langKey" => "fa",
            "browserMode" => "public",
            "otpInProgress" => "false",
            "currentStep" => "1",
            "pib_username" => $this->userName,
            "pib_password" => $this->password,
            "passwordType" => "S",
//            "." => "",
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
            "pib_username" => $this->userName,
            "pib_password" => $this->password,
            "passwordType" => "S",
            //"otpPassword" => "",
            "captcha" => "bbxhc",
        ];
    }

    public function makeid(int $length) {
        $result = '';
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[rand(0, $charactersLength-1)];
        }
        return $result;
    }

}

$parsianLogin = new ParsianLogin();

var_dump($parsianLogin->login());