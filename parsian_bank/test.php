<?php

$doc = new DOMDocument();
$file = fopen('responses/statementResponse.html', 'r');//paya/secondPage.html
$data = fread($file, 5000000);
fclose($file);

//$result = json_decode('{1"test" : "1234"}');
$result = json_decode($data);
$statement = [];
if((json_last_error() === JSON_ERROR_NONE))
{
    for($i=0;$i<$result->totalRecord;$i++)
    {
        $row = $result->rowDtoList[$i];
        $deposit['amount'] = $row->transferAmount;
        if($deposit['amount'] > 0){
            $deposit['date'] = $row->date;//change to jalali with jdate inputs are like date function
            $deposit['description'] = $row->description;
//            if(str_contains($row['description'],'تراکنش پُل')){
//                $deposit['serial'] =
//            }
            if(str_contains($row->description,'تراکنش پُل')){
                $deposit['peygiri'] = "bridge";
                $deposit['card_number'] = ''; // card number = card number ?? account number ?? sheba
            }
            if(str_contains($row->description,'انتقال از کارت')){
                preg_match_all('!\d{14}!', $row->description, $matches);
                $deposit['peygiri'] = "card to card";
                $deposit['card_number'] = $matches[0]; // card number = card number ?? account number ?? sheba
            }
            if(str_contains($row->description,'انتقالی حساب')){
//                preg_match_all('!\d{14}!', $row->description, $matches);
                preg_match_all('!فیش \d{7}!', $row->description, $peygiri);
                preg_match_all('!\d{7}!', $row->description, $matches);
                $deposit['peygiri'] = "account to account";
                $deposit['card_number'] = $matches; // card number = card number ?? account number ?? sheba
            }
            $deposit['serial'] = $row->referenceNumber; // serial = referenceNumber
//            $deposit['peygiri'] = $row->referenceNumber; // peygiri = peygiri ?? receipt number
            // if account to account then
            //  peygiri = receipt number
            // if bridge then
            // peygiri = peygiri
            // if card to card
            // peygiri = peygiri
            $deposit['erja'] = $deposit['peygiri'];

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
