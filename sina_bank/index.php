<?php 

try {

    require_once "./global.php";
    $doc = new DOMDocument();

    $loginData = getLoginData();

    // first curl for load of login page
    $firstUrl = 'https://ib.sinabank.ir/webbank/login/loginPage.action?ibReq=WEB';
    // $firstUrl = 'https://ib.sinabank.ir/webbank/login/captcha.action?isSoundCaptcha=false&r=3192574953940366';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_PROXY,PROXY);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD,PROXYUSERPWD);
    curl_setopt($ch, CURLOPT_URL, $firstUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: */*", 
        "Content-Type: application/x-www-form-urlencoded",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
    ]);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    $firstResponse = curl_exec($ch); // output: "";
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if($code >= 400 || $code == 0){
        var_dump("Failed in $firstUrl:",$code);die(curl_error($ch));
    }

    // second curl for load of login page
    // $secondUrl = 'https://ebank.shahr-bank.ir/ebank/dispatcherNamespace/dispatcherAction.action?ibReq=WEB';
    $secondUrl = 'https://ib.sinabank.ir/webbank/dispatcherNamespace/dispatcherAction.action?ibReq=WEB';
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
    writeOnFile('responses/thirdResponse.html', $code . $thirdResponse);
    if($code >= 400){
        var_dump("Failed in $firstUrl:",$code);die(curl_error($ch));
    }

    $loginData['loginToken'] = getInputTag($thirdResponse, $doc, '/<input type="hidden" name="loginToken" value=".*/'); //get current token
    var_dump($loginData);

    // curl for get captcha
    $captchaUrl = 'https://ib.sinabank.ir/webbank/login/captcha.action?isSoundCaptcha=false&r=3192574953940366';
    curl_setopt($ch, CURLOPT_URL, $captchaUrl);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
    $captchaRawImage = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($code >= 400){
        var_dump("Failed in $captchaUrl:",$code);die(curl_error($ch));
    }

    if ($captchaRawImage != '') {
        // save captcha image
        writeOnFile('images/captcha.png', $captchaRawImage);
        // get captcha code from user
        $loginData['captcha'] = readline('Enter the captcha:');
        var_dump($loginData);

        // curl for get sms code
        $loginUrl = "https://ib.sinabank.ir/webbank/login/login.action?ibReq=WEB&lang=fa";
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginData));
        $loginResponse = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        writeOnFile('responses/loginResponse.html', $code . $loginResponse);

        if (!$loginResponse) {
            echo "Login failed !" . PHP_EOL;
            exit;
        }
        $loginData2 = getLoginData2();
        $loginData2["ticketCode"] = readline('Enter the SMS:');

        $loginData2["ticketLoginToken"] = getInputTag($loginResponse, $doc, '/<input type="hidden" name="ticketLoginToken" value=".*/');
        $loginData2["mobileNumber"] = getInputTag($loginResponse, $doc, '/<input type="hidden" class="" name="mobileNumber" id="mobileNumber" value=".*/');
    }

    recreateNewFile(COOKIE_FILE);

}catch (\Exception $e) {
    echo $e->getMessage();
}

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

function getMetaTag(string $html, DOMDocument $doc, string $pattern)
{
    preg_match($pattern, $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
    $doc->loadHTML($text);
    var_dump($matches);
    $result = null;
    if ($doc->getElementsByTagName("meta"))
        $result = $doc->getElementsByTagName("meta")[0]->getAttribute("content");
    return $result;
}

function getLoginData() :array
{
    return [
        'struts.token.name' => 'loginToken',
        'otpSyncRequired' => 'false',
        'username' => 'meysam8900',
        'password' => 'SH@nyal0118DG',
        'loginType' => 'STATIC_PASSWORD',
        'isSoundCaptcha' => '',
        'soundCaptchaEnable' => 'true',
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