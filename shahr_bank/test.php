<?php 
# This is a comment
// d
/*
Yes
*/
$file = fopen('response/testsssss.html','r');
$data = fread($file , 5000000);
fclose($file);
var_dump(getDeposit($data, $user_id = 1, $banking_id = 1));
die;
$response = convertPersianNumberToEnglish($data);
preg_match_all('/<div class="item-field-info">(.*?)<\/div>/s', $response, $matches);
// preg_match_all('/[^0-9]/',$sync[0][0],$matches2);
$output = preg_replace( '/[^0-9]/', '', $matches[0] );
var_dump($output[0]);die;
        $text = "<html><body>
        $matches[0]
        </body></html>";
function convertPersianNumberToEnglish(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    return str_replace($persianNumber, $englishNumber, $text);
}
getDeposit($html, $user_id = 1, $banking_id = 1);
die;

// for ($i = 2; $trs->count() > $i; $i++) {
//     $descriptions = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
//     $cardNumber = (strpos($descriptions, 'به کارت') !== false && is_array($matches[0]) == true) ? $matches[0][1] : null;
//     var_dump($cardNumber);
// }
// die;
$str= "Amir";
Echo soundex($str);
echo metaphone($str);
die();
$doc = new DOMDocument();
$file = fopen('response/thirdResponse.html','r');
$data = fread($file , 5000000);

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
// function getDeposit(string $html) 
// {
//     $doc = new DOMDocument();
//     preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
//     $text = "<html><body>
//     $matches[0]
//     </body></html>";

//     $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
//     $trs = $doc->getElementsByTagName("tr");

//     for ($i = 1; $trs->count() > $i; $i++) {
//         $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
//         if (str_contains($deposit, "-")) { //just deposits, not withdrawals
//             continue;
//         } else {
//             $result[] = $deposit;
//         }

//     }
//     return $result;
// }

function getBalance(string $html)
{
        $doc = new DOMDocument();
        preg_match('/<table cellpadding="0" cellspacing="0">(.*?)<\/table>/s', $html, $matches);
        $text = "<html><body>
        $matches[0]
        </body></html>";

        $doc->loadHTML($text);
        $trs = $doc->getElementsByTagName("tr");

        for ($i = 1; $trs->count() > $i; $i++) {
            $account = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
            $total = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent;
            $balance = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
            $blocked = $trs->item($i)->getElementsByTagName("td")->item(5)->textContent;
            $result[] = [
                'account' => $account,
                'total' => $total,
                'balance' => $balance,
                'blocked_balance' => $blocked,
            ];
        }
        return $result;
}

$depHtml = fopen('response/depositeShowHtml.html','r');
$deposits = fread($depHtml,5000000);
fclose($depHtml);
var_dump(getDeposit($deposits));

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

        preg_match_all('!\d{16}!', $descriptions, $matches);
//        if($i == 5) {
//            if (isset($matches[0])) {
//                $cardNumber = ((strpos($descriptions, 'از کارت') !== false || strpos($descriptions,'انتقال وجه از') !== false) && is_array($matches[0]) == true) ? $matches[0][0] : null;
////                'انتقال وجه از'
//            }
//            var_dump($cardNumber);die;
//        }
        if (isset($matches[0])) {
//            $cardNumber = (strpos($descriptions, 'از کارت') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
            $cardNumber = ((strpos($descriptions, 'از کارت') !== false || strpos($descriptions,'انتقال وجه از') !== false) && is_array($matches[0]) == true) ? $matches[0][0] : null;
        }
        if (isset($matches[0])) {
            preg_match_all('!\d*:\d*:\d*!', $details, $matches);
            $hours = (strpos($details, 'ساعت') !== false && is_array($matches[0]) == true) ? formatTime($matches[0][0]) : "00:00:00";
            $datetime = str_replace(["‪", "‬"], "", "$date $hours");
            $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime);
        }

        preg_match_all('!\d{13}!', $details, $matches);
        if (isset($matches[0])) {
            $erja = (strpos($details, 'مرجع') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
        }

        preg_match_all('!\d{10}!', $details, $matches);
        if (isset($matches[0])) {
            $sanad = (strpos($details, 'سند') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
        }
        if(strpos($descriptions, 'پرداخت آني') !== false )
        {
            preg_match_all('!IR\d{24}!', $descriptions, $matches);
            if(isset($matches[0])) {
                $cardNumber = (strpos($descriptions, 'شبا') !== false && is_array($matches[0]) == true) ? $matches[0][0] : null;
            }
            preg_match_all('!\d{21}!', $descriptions, $matches);
            if(isset($matches[0])) {
                $erja = (strpos($descriptions, 'کدپيگيري') !== false && is_array($matches[0]) == true) ? $matches[0][1] : null;
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
//            ]),'shahr-statements');
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