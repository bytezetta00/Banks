<?php

require_once "./global.php";
    $doc = new DOMDocument();

    $queryParams = getQueryParams();
    $loginData = getLoginData();
    $mainPageHtml = checkLogin();

    if ($mainPageHtml['status_code'] < 400 && $mainPageHtml['body'] != '') {
        // $balance = getBalance($mainPageHtml, $doc);
        $runWithoutLogin = runWithoutLogin($mainPageHtml['body'], $doc);
        if($runWithoutLogin == true){
            exit;
        }
    }

    recreateNewFile(COOKIE_FILE);

    // first curl for load of login page
    $firstUrl = 'https://ebank.shahr-bank.ir/ebank/login/loginPage.action?ibReq=WEB';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_PROXY,PROXY);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD,PROXYUSERPWD);
    curl_setopt($ch, CURLOPT_URL, $firstUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json", 
        "Content-Type: application/x-www-form-urlencoded",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    ]);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    $firstResponse = curl_exec($ch); // output: "";
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($code >= 400 || $code == 0){
        var_dump("Failed in $firstUrl:",$code);die(curl_error($ch));
    }

    // second curl for load of login page
    $secondUrl = 'https://ebank.shahr-bank.ir/ebank/dispatcherNamespace/dispatcherAction.action?ibReq=WEB';
    curl_setopt($ch, CURLOPT_URL, $secondUrl);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    $secondResponse = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($code >= 400){
        var_dump("Failed in $secondUrl:",$code);die(curl_error($ch));
    }

    // third curl for load of login page
    curl_setopt($ch, CURLOPT_URL, $firstUrl);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    $thirdResponse = curl_exec($ch); // now whole html loaded
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    writeOnFile('response/thirdResponse.html', $code . $thirdResponse);
    if($code >= 400){
        var_dump("Failed in $firstUrl:",$code);die(curl_error($ch));
    }

    $loginData['loginToken'] = getInputTag($thirdResponse, $doc, '/<input type="hidden" name="loginToken" value=".*/'); //get current token
    
    // curl for get captcha
    $captchaUrl = 'https://ebank.shahr-bank.ir/ebank/login/captcha.action?isSoundCaptcha=false';
    curl_setopt($ch, CURLOPT_URL, $captchaUrl);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    $captchaRawImage = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($code >= 400){
        var_dump("Failed in $captchaUrl:",$code);die(curl_error($ch));
    }

    var_dump($captchaRawImage);



    function getInputTag(string $html, DOMDocument $doc, string $pattern)
    {
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
    
    function getBalance(string $html, DOMDocument $doc)
    {
        preg_match('/<div id="sourceSavingContainerInfo" class="smartComboInfo">.*/', $html, $matches);
    
        $text = "<html><body>
        $matches[0]
        </body></html>";
        $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $result = $doc->getElementById("sourceSavingContainerInfo");
        return $result->textContent;
    }
    
    function getBalanceFromDeposite(string $html , DOMDocument $doc){
        preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s',$html,$matches);
        $text = "<html><body>
        $matches[0]
        </body></html>";
        $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $trs = $doc->getElementsByTagName("tr");
        return $trs->item(1)->getElementsByTagName("td")->item(6)->textContent;
    }
    
    function getDeposit(string $html, DOMDocument $doc)
    {
        preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
        $text = "<html><body>
        $matches[0]
        </body></html>";
        $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumber = range(0, 9);
        $text = str_replace($persianNumber,$englishNumber,$text);
    
        $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $trs = $doc->getElementsByTagName("tr");
    
        for ($i = 1; $trs->count() > $i; $i++) {
            $descriptions = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
            $date = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent;
            $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
            $details = $trs->item($i)->getElementsByTagName("td")->item(8)->textContent;
    
            
            preg_match_all('!\d{16}!', $descriptions, $matches);
            $cardNumber = (strpos($descriptions,'به کارت') !== false) ? $matches[0][1]: null;
    
            preg_match_all('!\d*:\d*:\d*!', $details, $matches);
            $hours = (strpos($details,'ساعت') !== false) ? $matches[0][0]: "00:00:00";
            $datetime = "$date $hours";
            $bigintDatetime = str_replace(['/',':',' '],'',$datetime);
    
            preg_match_all('!\d{13}!', $details, $matches);
            $erja = (strpos($details,'مرجع') !== false) ? $matches[0][0]: null;
    
            preg_match_all('!\d{10}!', $details, $matches);
            $sanad = (strpos($details,'سند') !== false) ? $matches[0][0]: null;
    
            if (str_contains($deposit, "-")) {
                continue;
            } else {
                $result[] = [
                    'amount' => trim($deposit),
                    'erja' => $erja,
                    'peygiri' => $erja,
                    'serial' => $sanad,
                    'card_number' => $cardNumber,
                    'datetime' => $datetime,
                    'bigint_datetime' => $bigintDatetime,
                ];
            }
    
        }
        return $result;
    }
    
    function checkLogin()
    {
        $urlHomePage = 'https://ebank.shahr-bank.ir/ebank/home/homePage.action';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY,PROXY);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD,PROXYUSERPWD);
        curl_setopt($ch, CURLOPT_URL, $urlHomePage);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"]);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['body' => $result,'status_code' => $code];
    }
    
    function getQueryParams()
    {
        return [
            'ibReq' => 'WEB',
            'lang' => 'fa',
        ];
    }
    
    function getLoginData() :array
    {
        return [
            'username' => 'fango5826',
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
    
    function getLoginData2() :array
    {
        return [
            "struts.token.name" => "ticketLoginToken",
            "ticketResendTimerRemaining" => -1,
            "hiddenPass1" => 1,
            "hiddenPass2" => 2,
            "hiddenPass3" => 3,
        ];
    }
    
    function getBalanceData() :array
    {
        return [
            'struts.token.name' => "depositShowToken",
            // 'depositShowToken' => "PL7VMNHVKWZKM33V5NABJ2L6AG56WADJ",
            'advancedSearch' => true,
            // 'personalityType' => 
            // 'depositGroupByReq' => 
            // 'referenceCustomerName' => 
            // 'referenceCif' => 
            // 'ownershipType' => 
            // 'accountType' => 
            // 'currencyType' => 
            'maxLenForNote' => '200',
            'selectedDeposit' => '4001002408872', //TODO
            'selectedDepositValueType' => 'sourceDeposit',
            // 'selectedDepositPinnedDeposit' => 
            'selectedDepositIsComboValInStore' => false,
            // 'billType' => 
            'fromDateTime' => '1402/01/26  -  00:00',
            'toDateTime' => '1402/02/25  -  11:52',
            // 'minAmount' => 
            // 'currency' => 
            // 'currencyDefaultFractionDigits' => 
            // 'maxAmount' => 
            'order' => 'DESC',
            // 'desc' => 
            // 'paymentId' => 
        ];
    }
    
    function runWithoutLogin(string $mainPageHtml, DOMDocument $doc){
            $ch = curl_init();
    
            $selectDateUrl = 'https://ebank.shahr-bank.ir/ebank/viewAcc/partialDepositShow.action?daysAgo=30';
            curl_setopt($ch, CURLOPT_PROXY,PROXY);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD,PROXYUSERPWD);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"]);
            curl_setopt($ch, CURLOPT_URL, $selectDateUrl);
            curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
            $selectPageHtml = curl_exec($ch);
            if($selectPageHtml == false){
                return $selectPageHtml;
            }
            writeOnFile('response/selectPageHtml.html', $selectPageHtml);
    
            $balanceData = getBalanceData();
            $balanceData['depositShowToken'] = getInputTag($selectPageHtml, $doc, '/<input type="hidden" name="depositShowToken" value=".*/');
            $balanceData['fromDateTime'] = getInputTag($selectPageHtml, $doc, '/<input type="text" name="fromDateTime" id="fromDateTime" value=".*/');
            $balanceData['toDateTime'] = getInputTag($selectPageHtml, $doc, '/<input type="text" name="toDateTime" id="toDateTime" value=".*/');
            var_dump($balanceData);
    
            $depositeShowUrl = 'https://ebank.shahr-bank.ir/ebank/viewAcc/depositShow.action?' . http_build_query($balanceData);
            curl_setopt($ch, CURLOPT_URL, $depositeShowUrl);
            curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
            $depositeShowHtml = curl_exec($ch);
    
            if($depositeShowHtml == false){
                return $depositeShowHtml;
            }
            writeOnFile('response/depositeShowHtml.html', $depositeShowHtml);
    
            $viewDetailsAccountHtmlReportUrl = 'https://ebank.shahr-bank.ir/ebank/viewAcc/viewDetailsAccountHtmlReport.action?currency=IRR&ownership=BE_TANHAYEE&personality=ACTUAL';
            curl_setopt($ch, CURLOPT_URL, $viewDetailsAccountHtmlReportUrl);
            curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
            $viewDetailsAccountHtmlReport = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            writeOnFile('response/viewDetailsAccountHtmlReport.html', $code . $viewDetailsAccountHtmlReport);
    
            $getSourcesAndDestinationData = [
                "smartComboType" => "DEPOSIT",
                "showContacts" => "false",
                "businessType" => "all",
                "serviceName" => "",
                "currency" => "",
            ];
            $getSourcesAndDestinationsUrl = 'https://ebank.shahr-bank.ir/ebank/main/getSourcesAndDestinations.action';
            curl_setopt($ch, CURLOPT_URL, $getSourcesAndDestinationsUrl);
            curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($getSourcesAndDestinationData));
            $getSourcesAndDestinations = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            writeOnFile('response/getSourcesAndDestinations.html', $code . $getSourcesAndDestinations);
    
            $getBalanceDiagramStatementsData = [
                "selectedDeposit" => "4001002408872",
                "fromDateTime" => "1402/2/12 - 00:00",
                "toDateTime" => "1402/3/12 - 23:59",
                "order" => "DESC",
                "_" => time()
            ];
            $getBalanceDiagramStatementsUrl = 'https://ebank.shahr-bank.ir/ebank/viewAcc/getBalanceDiagramStatements.action'. http_build_query($getBalanceDiagramStatementsData);
            curl_setopt($ch, CURLOPT_URL, $getBalanceDiagramStatementsUrl);
            curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
            $getBalanceDiagramStatements = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            writeOnFile('response/getBalanceDiagramStatements.html', $code . $getBalanceDiagramStatements);
    
    
            // $loginDispatcherUrl = 'https://ebank.shahr-bank.ir/ebank/login/loginDispatcher.action?ibReq=WEB';
            // curl_setopt($ch, CURLOPT_URL, $loginDispatcherUrl);
            // curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
            // $loginDispatcher = curl_exec($ch);
            // $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // writeOnFile('response/loginDispatcher.html', $code . $loginDispatcher);
            
            // $loginPageActionUrl = 'https://ebank.shahr-bank.ir/ebank/login/loginPage.action?ibReq=WEB';
            // curl_setopt($ch, CURLOPT_URL, $loginPageActionUrl);
            // curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
            // $loginPageAction = curl_exec($ch);
            // $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // writeOnFile('response/loginPageAction.html', $code . $loginPageAction);
    
            // $loginPageActionUrl = 'https://ebank.shahr-bank.ir/ebank/dispatcherNamespace/dispatcherAction.action?ibReq=WEB';
            // curl_setopt($ch, CURLOPT_URL, $loginPageActionUrl);
            // curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
            // $loginPageAction = curl_exec($ch);
            // $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // writeOnFile('response/loginPageAction.html', $code . $loginPageAction);
            
            $balance = getBalanceFromDeposite( $depositeShowHtml , $doc);
            writeOnFile('balance.txt', 'Your last balance from Deposit:'.$balance);
    
            $deposits = getDeposit($depositeShowHtml, $doc);
            var_dump($deposits);
            var_dump('You have already logged in.');
    
            if(is_array($deposits)){
                $depositsString = implode(",",$deposits);
            }
            writeOnFile('deposits.txt', 'Your history deposit:'.$depositsString);
    
            curl_close($ch);
            return true;
    }