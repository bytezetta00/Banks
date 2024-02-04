<?php

require_once "./global.php";

// protected DOMDocument $domDocument;
// protected array $loginData;
// protected array $loginData2;

class Iranzamin
{

  public function __construct(
    private string $userName = "aa145900145900",
    private string $password = "145900145900#",
    private string $account = "3416-701-2128111-1",
    private string $proxy = PROXY,
    private string $proxyUserPwd = PROXYUSERPWD,
    )
  {
      $this->domDocument = new DOMDocument();
      $this->loginData = $this->getLoginData();
      $this->loginData2 = $this->getLoginData2();
  }

  public function login()
  {
     $loginFormUrl = "https://modern.izbank.ir/webbank/login/loginPage.action?ibReq=WEB";
     $loginFormResponse = $this->curlRequest($loginFormUrl);
     writeOnFile("responses/loginFormResponse.html",$loginFormResponse['body']);

     $captchaUrl = "https://modern.izbank.ir/webbank/login/captcha.action?isSoundCaptcha=false";
     $captchaResponse = $this->curlRequest($captchaUrl);
     $this->getDataFromLoginPage($loginFormResponse['body']);
     if ($captchaResponse["body"] != '') {
       writeOnFile("images/captcha.jpg",$captchaResponse['body']);
       $this->loginData['captcha'] = readline('Enter the captcha:');
     }

//     var_dump($this->loginData);
     $loginUrl = "https://modern.izbank.ir/webbank/login/login.action?ibReq=WEB&lang=fa";
     $loginResponse = $this->curlRequest($loginUrl,$this->loginData);
     writeOnFile("responses/loginResponse.html",$loginResponse['body']);

     $this->getDataFromTicketPage($loginResponse['body']);
     $this->loginData2["ticketCode"] = readline('Enter the SMS:');

//     var_dump($this->loginData2);
     $ticketUrl = "https://modern.izbank.ir/webbank/login/twoPhaseLoginWithTicket.action?ibReq=WEB&lang=fa";
     $ticketResponse = $this->curlRequest($ticketUrl,$this->loginData2);
     writeOnFile("responses/ticketResponse.html",$ticketResponse['body']);

     $homeUrl = "https://modern.izbank.ir/webbank/home/homePage.action";
     $homeResponse = $this->curlRequest($homeUrl);
     writeOnFile("responses/homeResponse.html",$homeResponse['body']);

     // https://modern.izbank.ir/webbank/viewAcc/getBalanceDiagramStatements.action?selectedDeposit=3416-701-2128111-1&fromDateTime=1402/10/7 - 00:00&toDateTime=1402/11/8 - 23:59&order=DESC&_=1706357517731
     // https://modern.izbank.ir/webbank/viewAcc/getBalanceDiagramStatements.action?selectedDeposit=3416-701-2128111-1&fromDateTime=1402/10/7 - 00:00&toDateTime=1402/11/8 - 23:59&order=DESC&_=1706357517732
     $balanceUrl = "https://modern.izbank.ir/webbank/viewAcc/viewAccAction.action";
     // https://modern.izbank.ir/webbank/viewAcc/viewDetails.action?accountType=PASANDAZ
     $balanceResponse = $this->curlRequest($balanceUrl);
     writeOnFile("responses/balanceResponse.html",$balanceResponse['body']);
      $balance = $this->getBalance($balanceResponse['body']);

     $defaultBillListurl = "https://modern.izbank.ir/webbank/viewAcc/defaultBillList.action?selectedDeposit=3416-701-2128111-1&accountType=PASANDAZ&currency=IRR";
     $defaultBillListResponse = $this->curlRequest($defaultBillListurl);
     writeOnFile("responses/defaultBillListResponse.html",$defaultBillListResponse['body']);
     $depositShowToken = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" name="depositShowToken" value=".*/') ?? '';
     $struts = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" name="struts.token.name" value=".*/') ?? 'depositShowToken';
     $advancedSearch = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" class="" name="advancedSearch" id="advancedSearch" value=".*/') ?? true;
     $accountType = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" class="" name="accountType" id="accountType" value=".*/') ?? "PASANDAZ1";
     $maxLenForNote = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" class="" name="maxLenForNote" id="maxLenForNote" value=".*/') ?? 200;
     $selectedDepositIsComboValInStore = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" class="" name="selectedDepositIsComboValInStore" id="selectedDepositIsComboValInStore" value=".*/') ?? false;
     $currency_selectedDeposit = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" class="" name="currency" id="currency_selectedDeposit" value=".*/') ?? 'IRR';
     $currencyDefaultFractionDigits = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" class="" name="currencyDefaultFractionDigits" id="currencyDefaultFractionDigits_selectedDeposit" value=".*/') ?? 2;
     $stmtIdnote1 = getInputTag($defaultBillListResponse['body'], '/<input type="hidden" class="" name="stmtIdnote1" id="stmtIdnote1" value=".*/') ?? "10635224_1706197421000_3416_1";
     $depositShowData = [
      'struts.token.name' => $struts,
      'depositShowToken' => $depositShowToken,
      'advancedSearch' => $advancedSearch,
      'personalityType' => '',
      'depositGroupByReq' => '',
      'referenceCustomerName' => '',
      'referenceCif' => '',
      'ownershipType' => '',
      'accountType' => $accountType,
      'currencyType' => '',
      'maxLenForNote' => $maxLenForNote,
      'selectedDeposit' => $this->account,
      'selectedDepositValueType' => 'sourceDeposit',
      'selectedDepositPinnedDeposit' => '',
      'selectedDepositIsComboValInStore' => $selectedDepositIsComboValInStore,
      'billType' => '',
      'fromDateTime' => '1402/11/04 - 00:35', // reform in server
      'toDateTime' => '1402/11/07 - 15:49', // reform in server
      'minAmount' => '',
      'currency' => $currency_selectedDeposit,
      'currencyDefaultFractionDigits' => $currencyDefaultFractionDigits,
      'maxAmount' => '',
      'order' => 'DESC',
      'desc' => '',
      'stmtIdnote1' => $stmtIdnote1,
      'stmtIdnote2' => $stmtIdnote1,
      'stmtIdnote3' => $stmtIdnote1,
    ];
    $depositShowUrl = "https://modern.izbank.ir/webbank/viewAcc/depositShow.action?".http_build_query($depositShowData);
    $depositShowResponse = $this->curlRequest($depositShowUrl);

    writeOnFile("responses/depositShowResponse.html",$depositShowResponse['body']);

    return $depositShowUrl;
  }

  public function getLoginData():array
  {
    return [
      // "struts.token.name" => "loginToken", //get from form
      // "loginToken" => "L02Z6GTEBE91QMD6XX1SZYJK4K781LJ4", //get from form
      // "otpSyncRequired" => "false", //get from form
      "username" => $this->userName, //get from form
      "hiddenPass1" => 1, //get from form
      "hiddenPass2" => 2, //get from form
      "hiddenPass3" => 3, //get from form
      "password" => $this->password,
      "loginType" => "STATIC_PASSWORD",
      // "captcha" => "",
      // "isSoundCaptcha" => false,
      // "soundCaptchaEnable" => false
    ];
  }

  public function getLoginData2():array
  {
    return [];
  }

  public function getDataFromLoginPage(string $html):void
  {
    $patternPass1 = '/<input type="password" class="" name="hiddenPass1" id="hiddenPass1"(.*?)value="(.*?)\/>/s';
    $patternPass2 = '/<input type="password" class="" name="hiddenPass2" id="hiddenPass2"(.*?)value="(.*?)\/>/s';
    $patternPass3 = '/<input type="password" class="" name="hiddenPass3" id="hiddenPass3"(.*?)value="(.*?)\/>/s';

    $this->loginData['loginToken'] = getInputTag($html, '/<input type="hidden" name="loginToken" value=".*/') ?? '';
    $this->loginData['struts.token.name'] = getInputTag($html, '/<input type="hidden" name="struts.token.name" value=".*/') ?? 'loginToken';
    $this->loginData['otpSyncRequired'] = getInputTag($html, '/<input type="hidden" class="" name="otpSyncRequired" id="otpSyncRequired" value=".*/') ?? false;
    $this->loginData['isSoundCaptcha'] = getInputTag($html, '/<input type="hidden" class="" name="isSoundCaptcha" id="isSoundCaptcha" value=".*/') ?? false;
    $this->loginData['soundCaptchaEnable'] = getInputTag($html, '/<input type="hidden" class="" name="soundCaptchaEnable" id="soundCaptchaEnable" value=".*/') ?? false;

    $this->loginData['hiddenPass1'] = getInputTag($html, $patternPass1) ?? 1;
    $this->loginData['hiddenPass2'] = getInputTag($html, $patternPass2) ?? 2;
    $this->loginData['hiddenPass3'] = getInputTag($html, $patternPass3) ?? 3;
  }

  public function getDataFromTicketPage(string $html):void
  {
    $patternPass1 = '/<input type="password" class="" name="hiddenPass1" id="hiddenPass1"(.*?)value="(.*?)\/>/s';
    $patternPass2 = '/<input type="password" class="" name="hiddenPass2" id="hiddenPass2"(.*?)value="(.*?)\/>/s';
    $patternPass3 = '/<input type="password" class="" name="hiddenPass3" id="hiddenPass3"(.*?)value="(.*?)\/>/s';

    $this->loginData2['ticketLoginToken'] = getInputTag($html, '/<input type="hidden" name="ticketLoginToken" value=".*/') ?? '';
    $this->loginData2['struts.token.name'] = getInputTag($html, '/<input type="hidden" name="struts.token.name" value=".*/') ?? 'ticketLoginToken';
    $this->loginData2['ticketResendTimerRemaining'] = getInputTag($html, '/<input type="hidden" class="" name="ticketResendTimerRemaining" id="ticketResendTimerRemaining" value=".*/') ?? false;
    $this->loginData2['mobileNumber'] = getInputTag($html, '/<input type="hidden" class="" name="mobileNumber" id="mobileNumber" value=".*/') ?? false;

    $this->loginData2['hiddenPass1'] = getInputTag($html, $patternPass1) ?? 1;
    $this->loginData2['hiddenPass2'] = getInputTag($html, $patternPass2) ?? 2;
    $this->loginData2['hiddenPass3'] = getInputTag($html, $patternPass3) ?? 3;
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

    public function getBalance(string $html,$account)
    {
        $doc = new DOMDocument();

        preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
        $text = "<html><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>
        $matches[0]
        </body></html>";

        $text = convertPersianNumberToEnglish($text);

        $internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($text);
        libxml_use_internal_errors($internalErrors);
        $trs = $doc->getElementsByTagName("tr");

        $result = false;
        for ($i = 2; $i < $trs->count(); $i++) {
            $accountNumber = $trs->item($i)->getElementsByTagName("td")->item(0)->textContent;
            if (strpos(setPersianFormatForBalance($accountNumber), $account) != false) {
                $balance = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(1)->textContent);
                $availableBalance = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(2)->textContent);
                $blocked = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(4)->textContent);
                $status = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(3)->textContent);

                $balance = (int)str_replace(',', '', $balance);
                $availableBalance = (int)str_replace(',', '', $availableBalance);
                $blockedBalance = $balance - $availableBalance;
                $result = [
                    'balance' => $balance,
                    'blocked_balance' => $blockedBalance
                ];
                if (strpos($status, "مسدود برداشت") !== false) {
                    $result["is_account_blocked"] = true;
                }
//            newLog(var_export($result,true)."\n\n".$status,'sina-balance-debug','sina');

                return $result;
            }
            return $result;
        }
    }

}

$login = (new Iranzamin())->login();
var_dump($login);

?>
