<?php

$doc = new DOMDocument();
$file = fopen('responses/statementResponse.html', 'r');//paya/secondPage.html
//$file = fopen('responses/getOpenTermAccountsResponse.html', 'r');
$data = fread($file, 5000000);
fclose($file);
$text = "رمز ورود به اینترنت بانک یا همراه بانک:
85743
مهلت استفاده دو دقیقه";
if((strpos($text,'بانک') !== false) && (strpos($text,'ورود') !== false)) {

    preg_match('!\d{5}!', $text, $matches);
    var_dump($matches[0]);

}
$date = date('Y-m-d h:i:s.000', time());
$formattedDate = str_replace(' ','T',"$date"."Z");

$previousDate = date('Y-m-d h:i:s.000', strtotime($date . ' -1 months'));
$formattedPreviousDate = str_replace(' ','T',"$previousDate"."Z");

var_dump($formattedPreviousDate);
die;
//$result = json_decode('{1"test" : "1234"}');
$result = getDeposits($html = $data, $user_id = 1, $banking_id = 1);
var_dump($result);
function getDeposits(string $html, $user_id, $banking_id)
{
    $result = json_decode($html);
    $statement = [];
    if ((json_last_error() === JSON_ERROR_NONE)) {
        if ($result == null || $result == "")
            return false;

        if (!key_exists('totalRecord', (array)$result))
            return false;

        for ($i = 0; $i < $result?->totalRecord; $i++) {
            $row = $result?->rowDtoList[$i];
            if ($row === null || $row === "")
                return false;

            $amount = $row?->transferAmount;
            if ($amount > 0) {
                $date = $row?->date;//change to jalali with jdate inputs are like date function
                $description = $row?->description;
                $serial = $row?->referenceNumber;
                if($serial == '' || $serial == null){
                    continue;
                }
                if (str_contains($description, 'تراکنش پُل')) {
                    preg_match('!کدِ رهگیریِ \d{20,22}!', $description, $firstTrackingNumberMatches);
                    if(!key_exists(0,$firstTrackingNumberMatches)){
                            continue;
                    }
                    preg_match('!\d{20,22}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
                    if(!key_exists(0,$secondTrackingNumberMatches)){
                        continue;
                    }
                    preg_match('!IR\d{24}!', $description, $shebaMatches);
                    if(!key_exists(0,$shebaMatches)){
                        continue;
                    }
                    $shebaNumber = $shebaMatches[0];
                    $trackingNumber = $secondTrackingNumberMatches[0];
                    $peygiri = $erja = $trackingNumber;
                    $cardNumber = $shebaNumber;
                }
                if (str_contains($description, 'انتقال از کارت')) {
                    preg_match('!از کارت \d{16}!', $description, $firstCardNumberMatches);
                    if(!key_exists(0,$firstCardNumberMatches)){
                        continue;
                    }
                    preg_match('!\d{16}!', $firstCardNumberMatches[0], $secondCardNumberMatches);
                    if(!key_exists(0,$secondCardNumberMatches)){
                        continue;
                    }
                    preg_match('!شماره پیگیری \d{5,7}!', $description, $firstTrackingNumberMatches);
                    if(!key_exists(0,$firstTrackingNumberMatches)){
                        continue;
                    }
                    preg_match('!\d{5,7}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
                    if(!key_exists(0,$secondTrackingNumberMatches)){
                        continue;
                    }

                    $peygiri = $erja = $secondTrackingNumberMatches[0];
                    $cardNumber = $secondCardNumberMatches[0];
                }
                if (str_contains($description, 'انتقالی حساب')) {
                    preg_match('!\d{12,14}!', $description, $accountMatches);
                    if(!key_exists(0,$accountMatches)){
                        continue;
                    }
                    preg_match('!فیش \d{6,8}!', $description, $firstReceiptNumberMatches);
                    if(!key_exists(0,$firstReceiptNumberMatches)){
                        continue;
                    }
                    preg_match('!\d{6,8}!', $firstReceiptNumberMatches[0], $secondReceiptNumberMatches);
                    if(!key_exists(0,$secondReceiptNumberMatches)){
                        continue;
                    }
                    $receiptNumber = $secondReceiptNumberMatches[0];
                    $peygiri = $erja = $receiptNumber;
                    $cardNumber = $accountMatches[0];
                }
//                $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, trim($serial)]);
//                if (str_contains($amount, "-") || $stt != false || $cardNumber == null) {
//                    continue;
//                }else {
                    $statement[] = [
                        $user_id, // user_id
                        $banking_id, // banking_id
                        trim(str_replace(',', '', $amount) ?? ''), // amount
                        trim($erja ?? ''), // erja
                        trim($peygiri ?? ''), // peygiri
                        trim($serial ?? ''), // serial
                        trim($cardNumber ?? ''), // card_number
//                      $datetime = str_replace(["‪", "‬"], "", jdate($date)),  // datetime
//                      $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime), // bigint_datetime
                    ];
//                }
            }
        }
    } else {
        return false;
    }
    return array_reverse($statement, true);
}

//var_dump($statement);
die;
//if((json_last_error() === JSON_ERROR_NONE))
//{
//    $accounts = $result->allAccountList;
//    $balance = [];
//    foreach ($accounts as $account){
//        if($account->depositNumber == 47001499225602){
//            $balance['balance'] = (int) $account->balance;
//            $balance['blockedAmount'] = (int) $account->balance - (int) $account->availableBalance;
//        }
//    }
//    var_dump($balance);
//}
//else{
//    var_dump("Invalid Json In Balance.");
//}
//var_dump(json_last_error());die; //['allAccountList']
