<?php

require_once "../global.php";
$doc = new DOMDocument();
$ch = curl_init();
$url = "https://ebank.shahr-bank.ir/ebank/viewAcc/viewDetailsAccountHtmlReport.action?currency=IRR&ownership=BE_TANHAYEE&personality=ACTUAL";
$firstUrl = 'https://ebank.shahr-bank.ir/ebank/login/loginPage.action?ibReq=WEB';
$ba = 'https://www.ba24.ir/';

// curl_setopt($ch, CURLOPT_URL , $url);
// curl_setopt($ch, CURLOPT_PROXY,PROXY);
// curl_setopt($ch, CURLOPT_PROXYUSERPWD,PROXYUSERPWD);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
// curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Content-Type: application/x-www-form-urlencoded"]);
// curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
// curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
// $result = curl_exec($ch);
// $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// var_dump($code,$result);die;
$main = fopen('./htmlForSeeBalance.html', 'r');//response/depositeShowHtml
$selectPageHtml = fread($main, 5000000);
fclose($main);
// var_dump($selectPageHtml);die;
$deposits = getDeposit($selectPageHtml, $doc);
var_dump($deposits);die;
if(is_array($deposits)){
    $depositsString = implode(",",$deposits);
}
writeOnFile('deposits.txt', 'Your history deposite:'.$depositsString);
var_dump('selectPageHtml');die;
// preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s',$selectPageHtml,$matches);
// $text = "<html><body>
// $matches[0]
// </body></html>";
// $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
// $trs = $doc->getElementsByTagName("tr");
// $amount = $trs->item(1)->getElementsByTagName("td")->item(6)->textContent;
// writeOnFile('balance.txt', 'Your last balance:'.$amount);
// var_dump($amount);die;
// var_dump($deposits);
// save final html


for($i=1;$trs->count()>$i;$i++)
{
    $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
    if(str_contains($deposit, "-")){
        continue;
    }
    else{
        $result[] = $deposit;
    }
    
}
die;
// $result = getInputTag($mainHtml , $doc, '/<input type="hidden" name="depositShowToken" value=".*/');
// $result = getInputTag($mainHtml , $doc, '/<input type="text" name="toDateTime" id="toDateTime" value=".*/');
$balanceData = [
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
    'selectedDeposit' => '4001002408872',
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
$balanceData['depositShowToken'] = getInputTag($selectPageHtml , $doc, '/<input type="hidden" name="depositShowToken" value=".*/');
$balanceData['fromDateTime'] = getInputTag($selectPageHtml , $doc, '/<input type="text" name="fromDateTime" id="fromDateTime" value=".*/');
$balanceData['toDateTime'] = getInputTag($selectPageHtml , $doc, '/<input type="text" name="toDateTime" id="toDateTime" value=".*/');
var_dump($balanceData);
// var_dump($result->textContent);

function getInputTag(string $html,DOMDocument $doc,string $pattern)
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

function getDeposit(string $html) 
{
    $doc = new DOMDocument();
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

        if (str_contains($deposit, "-")) { //just deposits, not withdrawals
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

function replace_accents($str) {
    $str = htmlentities($str, ENT_COMPAT, "UTF-8");
    // $str = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/','$1',$str);
    return html_entity_decode($str);
 }