<?php

$doc = new DOMDocument();
$file = fopen('responses/statementResponse.html', 'r');//paya/secondPage.html
//$file = fopen('responses/getOpenTermAccountsResponse.html', 'r');
$data = fread($file, 5000000);
fclose($file);
$description = "واریز پایا با مشخصات :  رهگیری: 140211180132036774  شناسه پرداخت: EMPTY  شناسه تراکنش: REFA14021118-03-000001.CT-5258  شرح: DRPA-EMPTY  نام: جمالي كاپك  شبا: IR370130100000000371522470  - رفاه‌کارگران ";
if(str_contains($description, 'واریز پایا')) {

    preg_match('!IR\d{24}!', $description, $shebaMatches);
    if (!key_exists(0, $shebaMatches)) {
//        continue;
    }
    preg_match('!رهگیری: \d{16,22}!', $description, $firstTrackingNumberMatches);
//    if (key_exists(0, $firstTrackingNumberMatches)) {
//        preg_match('!\d{16,22}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
//    }

    $shebaNumber = $shebaMatches[0];
    $trackingNumber = (isset($secondTrackingNumberMatches) && key_exists(0, $firstTrackingNumberMatches)) ? $secondTrackingNumberMatches[0] : 11;
    $peygiri = $erja = $trackingNumber;
    $cardNumber = $shebaNumber ?? 'paya';
    var_dump($secondTrackingNumberMatches[0]);

}die;
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
// solution( num_buns , num_required )
// len of array is num_buns
// if num_buns == num_required for 0 to num_buns difference every item len of every item is 1
// if num_required == 1 for 0 to num_buns same every item(0) len of every item is 1
// count of numbers that we used = num_required <= (num_buns * X)/num_required <= 10
//if num_buns % num_required != 0
//(all - num_required) * num_required  2 * 3
// num_required * X >= count of numbers that we used
//[0,1,2,3,4,5],
//[0,1,2,3,4,5],
//[0,1,6,7,8,9],
//[6,7,4,5,8,9],
//[2,3,8,9,7,6],
//[0,1,2],
//[3,4,5],
//[0,1,2],
//[3,4,5],
//[6,7,8],
//
//    (3/2) -> 3 -> 2
//    (5/3) -> 10 -> 6
//    (5/2) -> 10 -> 4


// counts of any number = num_buns - (num_required - 1)
// all the numbers should be in num_required rows and not (num_required - 1)
// 5-(3-1)
// num_required = 3 > count
// counts of any number = 3
//[
//    [0,1,2,3,4,5],
//    [0,1,2,6,7,8],
//    [0,3,4,6,7,9],
//    [1,3,5,6,8,9],
//    [2,4,5,7,8,9],
//]
//    [0,1,2,3,4],
//    [0,1,4],
//    [0,2,4],
//    [1,3],
//    [2,3],


// 5-(2-1)
// num_required = 2 > count
// counts of any number = 4
// if (counts of any number * used currently) % num_buns == 0
// count of every row is equal first one and last number finished
// when number finished if count of empty space % counts of any number == 0 continue otherwise add one to first one


// numbers that used in the matrix are finished, you know and
// if (1 to 5) with (1 to 5)   .... with (1 to 5)
// if (num_required - 1) == 1 check every row alone
// for j in (num_required - 1):
//     for i in mat
//