<?php 
# This is a comment
// d
/*
Yes
*/
//
$file = fopen('response/testsssss.html','r');
$data = fread($file , 5000000);
fclose($file);

var_dump(getDeposit($data,1,1));
//$pattern = '/<input type="hidden" name="normalAchTransferConfirmToken" value="(.*?)">/s';
//$pattern = '/<input type="password" class="" name="hiddenPass3" id="hiddenPass3"(.*?)value="(.*?)\/>/s';
//$patternPass1 = '/<input type="password" class="" name="hiddenPass1" id="hiddenPass1"(.*?)value="(.*?)\/>/s';
//$patternPass2 = '/<input type="password" class="" name="hiddenPass2" id="hiddenPass2"(.*?)value="(.*?)\/>/s';
//$patternPass3 = '/<input type="password" class="" name="hiddenPass3" id="hiddenPass3"(.*?)value="(.*?)\/>/s';

//$loginData['hiddenPass1'] = getInputTag($data, $patternPass1) ?? 9;
//$loginData['hiddenPass2'] = getInputTag($data, $patternPass2) ?? 8;
//$loginData['hiddenPass3'] = getInputTag($data, $patternPass3) ?? 7;
//var_dump($loginData);die;

$message['message'] = "بانک شهر
بليت امنيتي انتقال وجه پایا عادی
از 4001003190448
به IR750600600702515295271001
مبلغ 56,000,000 ريال
بليت: 519733
اعتبار تا 14:15";
if ((strpos($message['message'], 'بانک شهر') !== false) || (strpos($message['message'], 'پایا') !== false)) {
    preg_match_all('! \d{6}! ', $message['message'], $matches);
    if (isset($matches[0][1])) {
        var_dump($matches[0][1]);
    }
}die;

//
//die;

function convertPersianNumberToEnglish(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    return str_replace($persianNumber, $englishNumber, $text);
}


$internalErrors = libxml_use_internal_errors(true);
$doc->loadHTML($data);
libxml_use_internal_errors($internalErrors);
$door = $doc->getElementsByTagName("input");
var_dump($door);die;
function formatTime($time){
    $timeParts = explode(":", $time);
    if(count($timeParts) == 3)
    {
        $hours = str_pad($timeParts[0], 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($timeParts[1], 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($timeParts[2], 2, '0', STR_PAD_LEFT);
        return "$hours:$minutes:$seconds";
    }
    return $time;
}
var_dump(formatTime("9:2"));die;
$file = fopen('response/viewDetailsAccountHtmlReport.html','r');
$data = fread($file , 5000000);
fclose($file);
var_dump(getBalance($data));die;
// var_dump(json_decode($data)->aaData[0]->balance);die;

require_once "./version3/shahrbank.php";
$data =[
    'account' => '111', 
    'username' => '222', 
    'password' => '333', 
];
var_dump(
    (new ShahrBank(
        $data,
        444
    ))
);
die;

function getBalance(string $html)
{
    $doc = new DOMDocument();
    preg_match('/<table cellpadding="0" cellspacing="0">(.*?)<\/table>/s', $html, $matches);
//    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);


//    return $matches;
    $text = "<html><body>
    $matches[0]
    </body></html>";

    $text = convertPersianNumberToEnglish($text);

    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML($text);
    libxml_use_internal_errors($internalErrors);


    $trs = $doc->getElementsByTagName("tr");

    $result = [];
    for ($i = 1; $trs->count() > $i; $i++) {
        $status = $trs->item($i)->getElementsByTagName("td")->item(2)->textContent;
        $total = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent;
        $balance = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
        $blocked = $trs->item($i)->getElementsByTagName("td")->item(5)->textContent;
//        $account = $trs->item($i)->getElementsByTagName("td")->item(0)->textContent;
//        preg_match_all('!\d{13}!', $account, $matches);
//        $account = $matches[0][0];
//        $balance = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
//        $status = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent;
//        $blocked = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
//        return $matches[0][0];
//        return $trs->item($i)->getElementsByTagName("td")->item(0)->textContent;

//        if ($currentAccount == $account) {
            $result = [
                'balance' => str_replace(',', '', $balance),
                'blocked_balance' => str_replace(',', '', $blocked)
            ];
            if (strpos($status, "مسدود برداشت ") !== false) {
                $result["is_account_blocked"] = true;
            }
//        }
    }

    return $result;
}

function getDeposit(string $html, $user_id, $banking_id)
{
    $doc = new DOMDocument();
    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";

    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    $text = str_replace($persianNumber, $englishNumber, $text);

    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_use_internal_errors($internalErrors);

    $trs = $doc->getElementsByTagName("tr");

    $result = [];
    for ($i = 1; $trs->count() > $i; $i++) {
        $descriptions = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
        $date = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent;
        $deposit = trim($trs->item($i)->getElementsByTagName("td")->item(4)->textContent);
        $details = $trs->item($i)->getElementsByTagName("td")->item(8)->textContent;

        preg_match_all('!\d{12,16}!', $descriptions, $matches);
//        if($i == 5) {
//            if (isset($matches[0])) {
//                $cardNumber = ((strpos($descriptions, 'از کارت') !== false || strpos($descriptions,'انتقال وجه از') !== false) && is_array($matches[0]) == true) ? $matches[0][0] : null;
////                'انتقال وجه از'
//            }
//            var_dump($cardNumber);die;
//        }
//        strpos($descriptions, "انتقال وجه اينترنتي از سپرده")
        if (isset($matches[0])) {
//            $cardNumber = (strpos($descriptions, 'از کارت') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
            $cardNumber = (((strpos($descriptions, 'از کارت') !== false || strpos($descriptions,'انتقال وجه از') !== false || strpos($descriptions,"از سپرده") !== false)) && is_array($matches[0]) == true  && empty($matches[0]) == false) ? $matches[0][0] : null;
        }
        if (isset($matches[0])) {
            preg_match_all('!\d*:\d*:\d*!', $details, $matches);
            $hours = (strpos($details, 'ساعت') !== false && is_array($matches[0]) == true && empty($matches[0]) == false) ? formatTime($matches[0][0]) : "00:00:00";
            $datetime = str_replace(["‪", "‬"], "", "$date $hours");
            $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime);
        }

        preg_match_all('!\d{11,13}!', $details, $matches);
//        var_dump(empty($matches[0]));
        if (isset($matches[0])) {
            $erja = (strpos($details, 'مرجع') !== false && is_array($matches[0]) == true && empty($matches[0]) == false) ? $matches[0][0] : null;
        }

        preg_match_all('!\d{10}!', $details, $matches);
        if (isset($matches[0])) {
            $sanad = (strpos($details, 'سند') !== false && is_array($matches[0]) == true && empty($matches[0]) == false) ? $matches[0][0] : null;
        }
        if(strpos($descriptions, 'پرداخت آني') !== false )
        {
            preg_match_all('!IR\d{24}!', $descriptions, $matches);
            if(isset($matches[0])) {
                $cardNumber = (strpos($descriptions, 'شبا') !== false && is_array($matches[0]) == true && empty($matches[0]) == false) ? $matches[0][0] : null;
            }
            preg_match_all('!\d{21}!', $descriptions, $matches);
            if(isset($matches[0])) {
                $erja = (strpos($descriptions, 'کدپيگيري') !== false && is_array($matches[0]) == true && empty($matches[0]) == false) ? $matches[0][1] : null;
            }
        }
//        $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, trim($sanad)]);
//        if($banking_id == 111){
//            newLog(json_encode([
//                $user_id, //user_id
//                $banking_id, //banking_id
//                trim(str_replace(',', '', $deposit) ?? ''), // amount
//                trim($erja ?? ''), //erja
//                trim($erja ?? ''), //peygiri
//                trim($sanad ?? ''), //serial
//                trim($cardNumber ?? ''), //card_number
//                $datetime, //datetime
//                $bigintDatetime, //bigint_datetime
//            ]),'shahr-statements','shahr');
//        }
//        if (str_contains($deposit, "-") || $stt != false || $cardNumber == null) { //just deposits, not withdrawals, only first time
        if ($cardNumber == null)
        {
            continue;
        } elseif (isset($erja, $sanad, $datetime)) {
            $result[] = [
                $user_id, //user_id
                $banking_id, //banking_id
                trim(str_replace(',', '', $deposit) ?? ''), // amount
                trim($erja ?? ''), //erja
                trim($erja ?? ''), //peygiri
                trim($sanad ?? ''), //serial
                trim($cardNumber ?? ''), //card_number
                $datetime, //datetime
                $bigintDatetime, //bigint_datetime
            ];
        }
    }
    return $result;
}

function setPersianFormatForBalance(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    $encodedText = mb_convert_encoding(
        $text,
        'ISO-8859-1',
        'UTF-8');
    $encodedText = $text;
    return trim(
        str_replace(
            $persianNumber,$englishNumber,$encodedText
        ));
}

function getMetaTag(string $html, string $pattern)
{
    $doc = new DOMDocument();

    preg_match($pattern, $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
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
    $text = "<html><body>
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