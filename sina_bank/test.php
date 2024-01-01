<?php

$doc = new DOMDocument();
$file = fopen('responses/test1.html', 'r');//paya/secondPage.html
$data = fread($file, 5000000);
fclose($file);
var_dump(getBalance($data,'163-813-9973057-1'));die;

//$textSMS = "510832
//بليط امنيتي
//انتقال وجه پایا عادی
//از: ‪373-873-5006287-1‬
//به: ‪IR170590016381309973057001‬
//مبلغ 10,000 ريال
//به نام: رنيا
//زمان اعتبار رمز 14:20:29
//*بانک سينا*";
//
//preg_match('!\d{6}!', $textSMS, $matches);
//preg_match_all('! \d{7,8}!', $textSMS, $matches);
//var_dump($matches[0]);die;
//$successfulText = 'انتقال وجه بین بانکی پایا عادی ثبت شد.';


$newNormalAchUrlResponse = convertPersianNumberToEnglish(+$data);
$pattern = '/<input type="hidden" name="normalAchTransferConfirmToken" value="(.*?)">/s';
preg_match_all('/<div class="formSection noTitleSection transferReceipt" id="">(.*?)<div class="commandBar/s', $newNormalAchUrlResponse, $matches1);
preg_match_all('/<span class="form-item-field " id="">(.*?)<\/span>/s', $matches1[0][0], $matches2);
$name = $matches2[0][5];
preg_match_all('!\d{19,21}!', $matches2[0][0], $matches3);
$peygiri = $matches3[0][0];
preg_match_all('/به نام (.*?)<\/span>/s', $name, $matches4);
$dest = trim($matches4[1][0]);
var_dump([
    $dest,
    $peygiri
]);die;
//$normalAchTransferConfirmToken = getInputTag($data,$pattern);
var_dump($normalAchTransferConfirmToken);
die;

$pattern = '/<meta name="CSRF_TOKEN" content=.*>/';
$input = getMetaTag($data,$pattern);
if($input === false){
    var_dump("Not found this pattern: $pattern");
    return false;
}
var_dump($input);
die;
//$input = getInputTag($data,'/<input type="hidden" name="normalAchTransferToken" value=".*/');
//$input = preg_match('/<input type="hidden" name="normalAchTransferToken" value="(.*?)\/>/s', $data, $matches);

// if(strpos($data ,"موجودی") == false || strpos($data , "مبلغ مسدودی") == false){
//     $accounts = getAccountsLinks($data);
//     foreach($accounts as $account){
//         var_dump("https://ib.sinabank.ir/webbank/viewAcc/$account");
//     }
//     // "https://ib.sinabank.ir/webbank/viewAcc/viewDetails.action?accountType=PASANDAZ";
// }

function getBalance(string $html,$account)
{
    $doc = new DOMDocument();

    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";

    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML($text);
    libxml_use_internal_errors($internalErrors);
    $trs = $doc->getElementsByTagName("tr");
    $result = false;
    for ($i = 1;$i < $trs->count(); $i++){
        $accountNumber = $trs->item($i)->getElementsByTagName("td")->item(0)->textContent;
        var_dump(setPersianFormatForBalance($accountNumber));
        if(strpos(setPersianFormatForBalance($accountNumber) ,$account) != false){
            $balance = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(1)->textContent);
            $availableBalance = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(2)->textContent);
            $blocked = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(4)->textContent);
            $status = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(3)->textContent);

            $balance = (int)str_replace(',', '',$balance);
            $availableBalance = (int)str_replace(',', '',$availableBalance);
            $blockedBalance = $balance - $availableBalance;
            $result = [
                'balance' => $balance,
                'blocked_balance' => $blockedBalance
            ];
            if(strpos($status , "مسدود برداشت") !== false)
            {
                $result["is_account_blocked"] = true;
            }
//            newLog(var_export($result,true)."\n\n".$status,'sina-balance-debug','sina');
            return $result;
        }

    }

    return $result;
}
die;


function getAccountsLinks($html)
{
    $doc = new DOMDocument();
    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";

    $text = convertPersianNumberToEnglish($text);
    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML($text);
    libxml_use_internal_errors($internalErrors);
    $trs = $doc->getElementsByTagName("tr");
    $link = [];
    for($i = 1; $i < $trs->count();$i++){
        if($trs->item($i)->getElementsByTagName("td")->item(2)->textContent > 0){
            $link[] = $trs->item($i)->getElementsByTagName("td")->item(0)->childNodes->item(0)->getAttribute("href");
        }
    }
    return $link;
}

$text = ["صدرا محمدی کلاسی"];
$text2 = null;

$name=trim($text2);
// $name= mysql_real_escape_string($name);
$err = "";
// if (preg_match('/^[^\x{600}-\x{6FF}]+$/u', str_replace("\\\\","",$name))){$err.= "Please use Persian characters!";
// }
echo preg_match('/^[^\x{600}-\x{6FF}]+$/u', str_replace("\\\\","",$name),$matches);echo PHP_EOL;
echo str_replace("\\\\","",$name);
var_dump(mb_ord('ی'));
var_dump(mb_ord('ي'));die;

$textForSms = "لطفا بلیت امنیتی ارسال شده به تلفن همراه ";
$textForSms = "لطفا بfejknggnjdsه ";
$line = strpos($data,$textForSms);
var_dump($line);die;
function getMetaTag(string $html, string $pattern)
{
    $doc = new DOMDocument();
    preg_match($pattern, $html, $matches);
    $text = ($matches[0] == null) ? false : $matches[0];
    if($text === false){
        return $text;
    }
    $doc->loadHTML($text);
    $result = null;
    if ($doc->getElementsByTagName("meta"))
        $result = $doc->getElementsByTagName("meta")[0]->getAttribute("content");
    return $result;
}

function getInputTag(string $html, string $pattern)
{
    $doc = new DOMDocument();
    preg_match($pattern, $html, $matches);
    $text = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>
    $matches[0]
    </body></html>";
    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML($text);
    libxml_use_internal_errors($internalErrors);


    $result = null;
    $input = $doc->getElementsByTagName("input");
    if (isset($input[0]))
        $result = $input[0]->getAttribute("value");
    return $result;
}

function convertToString($text):?string
{
    $stringText = match (true) {
        (is_string($text) == true) => $text,
        (is_numeric($text) == true) => "$text",
        (is_null($text) == true) => $text ?? 'null',
        (is_array($text) == true) => implode("&",array_map(function($a) {return (is_array($a) == true) ? json_encode($a) : $a;},$text)),
        (is_bool($text) == true) => ($text) ? "true" : "false",
        default => var_export($text),
    };
    return $stringText;
}

function formatTime($time)
{
    $timeParts = explode(":", $time);
    if (count($timeParts) == 3) {
        $hours = str_pad($timeParts[0], 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($timeParts[1], 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($timeParts[2], 2, '0', STR_PAD_LEFT);
        return "$hours:$minutes:$seconds";
    }
    return $time;
}
// var_dump(getDeposits($data, $doc));
function getDeposits(string $html,$user_id, $banking_id)
{
    $doc = new DOMDocument();
    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>
    $matches[0]
    </body></html>";

    $text = convertPersianNumberToEnglish($text);
    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_use_internal_errors($internalErrors);
    $trs = $doc->getElementsByTagName("tr");
    $result = [];
    for ($i = 1; $trs->count() > $i; $i++) {
        $details = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent; // this is "sharh" it has bunch of data, we need
        $date = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent; // date
        $deposit = convertPersianNumberToEnglish(trim($trs->item($i)->getElementsByTagName("td")->item(4)->textContent)); // deposit
        $description = $trs->item($i)->getElementsByTagName("td")->item(8)->textContent; // shomareh sanad(document id) & saat(hour-time)

        preg_match_all('!\d*:\d*:\d*!', $description, $matches);
        if(isset($matches[0])) {
            $hour = (strpos($description, 'ساعت') !== false && is_array($matches[0]) == true) ? formatTime($matches[0][0]) : "00:00:00";
            $datetime = str_replace(["‪", "‬"], "", "$date $hour");
            $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime);
        }

        preg_match_all('! \d{7,8}!', $description, $matches);
        if(isset($matches[0][0])) {
            $sanad = (strpos($description, 'سند') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
        }

        preg_match_all('! [A-Z0-9]{12} !', $details, $matches);
        if(isset($matches[0])) {
            $erja = (strpos($details, 'ش م') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
        }

        preg_match_all('! \d{16}!', $details, $matches);
        if(isset($matches[0])) {
            $cardNumber = (strpos($details, 'از ک') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
        }
        if(strpos($details, 'انتقال وجه آني از شبا') !== false )
        {
            preg_match_all('!IR\d{24}!', $details, $matches);
            if(isset($matches[0])) {
                $cardNumber = (strpos($details, 'شبا') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
            }
            preg_match_all('!\d{21}!', $details, $matches);
            if(isset($matches[0])) {
                $erja = (strpos($details, 'ش.پ') !== false && is_array($matches[0]) == true) ? $matches[0][1] : null;
            }
        }
//        $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, trim($sanad)]);|| $stt != false
        if (str_contains($deposit, "-") || $cardNumber == null) {
            continue;
        } elseif(isset($erja,$sanad,$datetime)) {
            $result[] = [
                $user_id, // user_id
                $banking_id, // banking_id
                trim(str_replace(',','',$deposit) ?? ''), // amount
                trim($erja ?? ''), // erja
                trim($erja ?? ''), // peygiri
                trim($sanad ?? ''), // serial
                trim($cardNumber ?? ''), // card_number
                $datetime, // datetime
                $bigintDatetime, // bigint_datetime
            ];
        }
    }
//    $result = array_reverse($result,true);
    return $result;
}

function convertPersianNumberToEnglish(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    return str_replace($persianNumber, $englishNumber, $text);
}
// var_dump(getBalance($data));
die;
$account = "1234";
$depositShowData = [
    "struts.token.name" => "depositShowToken",
    "depositShowToken" => "JWDF471CGPGBZJIHO75ZIE67VA78384Y",
    "advancedSearch" => "true",
    "personalityType" => "",
    "depositGroupByReq" => "",
    "referenceCustomerName" => "",
    "referenceCif" => "",
    "ownershipType" => "",
    "accountType" => "JARI_ACCOUNT",
    "currencyType" => "",
    "maxLenForNote" => "200",
    "selectedDeposit" => $account,
    "selectedDepositValueType" => "sourceDeposit",
    "selectedDepositPinnedDeposit" => "",
    "selectedDepositIsComboValInStore" => "false",
    "billType" => "",
    "fromDateTime" => "1402/04/2 - 06:59",
    "toDateTime" => "1402/05/2 - 14:10",
    "minAmount" => "",
    "currency" => "IRR",
    "currencyDefaultFractionDigits" => "2",
    "maxAmount" => "",
    "order" => "DESC",
    "desc" => "",
    "paymentId" => "",
    "stmtIdnote1" => "30532896_1688959762000_331_1"
];
var_dump(http_build_query($depositShowData));
die;


function setPersianFormatForBalance(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    $encodedText = mb_convert_encoding(
        $text,
        'ISO-8859-1',
        'UTF-8');
    return trim(
        str_replace(
            $persianNumber,$englishNumber,$encodedText
        ));
}