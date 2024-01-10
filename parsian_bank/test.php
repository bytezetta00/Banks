<?php

$doc = new DOMDocument();
$file = fopen('responses/statementResponse.html', 'r');//paya/secondPage.html
//$file = fopen('responses/getOpenTermAccountsResponse.html', 'r');
$data = fread($file, 5000000);
fclose($file);

//$result = json_decode('{1"test" : "1234"}');
$result = json_decode($data);
$statement = [];
if((json_last_error() === JSON_ERROR_NONE))
{
    if($result == null || $result == ""){
        return false;
    }

    if(!key_exists('totalRecord',(array) $result)){
        return false;
    }

    for($i=0;$i<$result?->totalRecord;$i++)
    {
        $row = $result?->rowDtoList[$i];
        if($row === null || $row === ""){
            return false;
        }
        $deposit['amount'] = $row?->transferAmount;
        if($deposit['amount'] > 0){
            $deposit['date'] = $row?->date;//change to jalali with jdate inputs are like date function
            $deposit['description'] = $row?->description;
//            if(str_contains($row['description'],'تراکنش پُل')){
//                $deposit['serial'] =
//            }
            $deposit['serial'] = $row?->referenceNumber; // serial = referenceNumber
            if(str_contains($row?->description,'تراکنش پُل')){
                preg_match('!کدِ رهگیریِ \d{20,22}!', $row->description, $firstTrackingNumberMatches);
                preg_match('!\d{20,22}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
                preg_match('!IR\d{24}!', $row->description, $shebaMatches);
                $shebaNumber = $shebaMatches[0];
                $trackingNumber = $secondTrackingNumberMatches[0];
                $deposit['flag'] = "bridge";
                $deposit['peygiri'] = $trackingNumber;
                $deposit['card_number'] = $shebaNumber; // card number = card number ?? account number ?? sheba
                $deposit['erja'] = $deposit['peygiri'];
            }
            if(str_contains($row->description,'انتقال از کارت')){
                preg_match('!از کارت \d{16}!', $row->description, $firstCardNumberMatches);
                preg_match('!\d{16}!', $firstCardNumberMatches[0], $secondCardNumberMatches);
                preg_match('!شماره پیگیری \d{5,7}!', $row->description, $firstTrackingNumberMatches);
                preg_match_all('!\d{5,7}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);

                $deposit['flag'] = "card to card";
                $deposit['peygiri'] = "card to card";
                $deposit['card_number'] = $secondCardNumberMatches[0]; // card number = card number ?? account number ?? sheba
                $deposit['erja'] = $deposit['peygiri'];
            }
            if(str_contains($row->description,'انتقالی حساب')){
                preg_match_all('!\d{14}!', $row->description, $accountMatches);
                preg_match('!فیش \d{6,8}!', $row->description, $firstReceiptNumberMatches);
                preg_match('!\d{6,8}!', $firstReceiptNumberMatches[0], $secondReceiptNumberMatches);
//                var_dump($accountMatches);
                $receiptNumber = $secondReceiptNumberMatches[0];
                $deposit['flag'] = "account to account";
                $deposit['peygiri'] = $receiptNumber;
                $deposit['erja'] = $receiptNumber;
                $deposit['card_number'] = $accountMatches[0][0]; // card number = card number ?? account number ?? sheba
            }
//            $deposit['peygiri'] = $row->referenceNumber; // peygiri = peygiri ?? receipt number
            // if account to account then
            //  peygiri = receipt number
            // if bridge then
            // peygiri = peygiri
            // if card to card
            // peygiri = peygiri

            $statement[] = $deposit;
        }

        //->description

//        amount    (OK)
//        erja      (OK)
//        peygiri  peygiri or receipt number
//        serial  erja or serial
//        card_number  card number or account number or sheba (OK)
//        datetime (OK)
//        bigint_datetime (OK)
    }
}
var_dump($statement);
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
