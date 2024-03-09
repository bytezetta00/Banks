<?php
load('date');

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
            $peygiri = $erja = $cardNumber = null;
            $row = $result?->rowDtoList[$i];
            if ($row === null || $row === "")
                return false;

            $amount = $row?->transferAmount;
            if ($amount > 0) {
                $date = $row?->date;
                $description = $row?->description;
                $serial = $row?->referenceNumber;
                $standardizedDate = substr($date,0,10);
                $jalaliDate = jdate("Y/m/d H:i:s",$standardizedDate);
                $datetime = str_replace(["‪", "‬"], "", $jalaliDate);
                if ($serial == '' || $serial == null) {
                    continue;
                }
                if (str_contains($description, 'تراکنش پُل')) {
                    preg_match('!کدِ رهگیریِ \d{20,22}!', $description, $firstTrackingNumberMatches);
                    if (!key_exists(0, $firstTrackingNumberMatches)) {
                        continue;
                    }
                    preg_match('!\d{20,22}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
                    if (!key_exists(0, $secondTrackingNumberMatches)) {
                        continue;
                    }
                    preg_match('!IR\d{24}!', $description, $shebaMatches);
                    if (!key_exists(0, $shebaMatches)) {
                        continue;
                    }
                    $shebaNumber = $shebaMatches[0];
                    $trackingNumber = $secondTrackingNumberMatches[0];
                    $peygiri = $erja = $trackingNumber;
                    $cardNumber = $shebaNumber ?? 'pol';
                }
                if (str_contains($description, 'انتقال از کارت')) {
                    preg_match('!از کارت \d{16}!', $description, $firstCardNumberMatches);
                    if (!key_exists(0, $firstCardNumberMatches)) {
                        continue;
                    }
                    preg_match('!\d{16}!', $firstCardNumberMatches[0], $secondCardNumberMatches);
                    if (!key_exists(0, $secondCardNumberMatches)) {
                        continue;
                    }
                    preg_match('!شماره پیگیری \d{5,7}!', $description, $firstTrackingNumberMatches);
                    if (!key_exists(0, $firstTrackingNumberMatches)) {
                        continue;
                    }
                    preg_match('!\d{5,7}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
                    if (!key_exists(0, $secondTrackingNumberMatches)) {
                        continue;
                    }

                    $peygiri = $erja = $secondTrackingNumberMatches[0];
                    $cardNumber = $secondCardNumberMatches[0];
                }
                if (str_contains($description, 'انتقالی حساب') || str_contains($description, 'از حساب')) {
                    preg_match('!\d{12,14}!', $description, $accountMatches);
                    if (!key_exists(0, $accountMatches)) {
                        continue;
                    }
                    preg_match('!فیش \d{6,8}!', $description, $firstReceiptNumberMatches);
                    if(key_exists(0, $firstReceiptNumberMatches)) {
                        preg_match('!\d{6,8}!', $firstReceiptNumberMatches[0], $secondReceiptNumberMatches);
                        if (!key_exists(0, $secondReceiptNumberMatches)) {
                            continue;
                        }
                    }
                    $receiptNumber = $secondReceiptNumberMatches[0] ?? $serial;
                    $peygiri = $erja = $receiptNumber;
                    $cardNumber = $accountMatches[0];
                }
                if(str_contains($description, 'واریز پایا')) {

                    preg_match('!IR\d{24}!', $description, $shebaMatches);
                    if (!key_exists(0, $shebaMatches)) {
                        continue;
                    }
                    preg_match('!رهگیری: \d{16,22}!', $description, $firstTrackingNumberMatches);
                    if (key_exists(0, $firstTrackingNumberMatches)) {
                        preg_match('!\d{16,22}!', $firstTrackingNumberMatches[0], $secondTrackingNumberMatches);
                    }

                    $shebaNumber = $shebaMatches[0];
                    $trackingNumber = (isset($secondTrackingNumberMatches) && key_exists(0, $firstTrackingNumberMatches)) ? $secondTrackingNumberMatches[0] : $row?->serial;
                    $peygiri = $erja = $trackingNumber;
                    $cardNumber = $shebaNumber ?? 'paya';

                }
                $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, trim($serial)]);
                $finalArray = [
                    $user_id, // user_id
                    $banking_id, // banking_id
                    trim(str_replace(',', '', $amount) ?? ''), // amount
                    trim($erja ?? ''), // erja
                    trim($peygiri ?? ''), // peygiri
                    trim($serial ?? ''), // serial
                    trim($cardNumber ?? ''), // card_number
                    $datetime,  // datetime
                    str_replace(['/', ':', ' '], '', $datetime), // bigint_datetime
                ];
//                newLog(var_export($finalArray,true),"parsian-statementsFinal",'parsian');
                if (str_contains($amount, "-") || $stt != false || $cardNumber == null) {
                    continue;
                } else {
                    $statement[] = $finalArray;
                }
            }
        }
    } else {
        return false;
    }
    return array_reverse($statement, true);
}

function getBalance(string $html,$accountNumber)
{
    if($html != null && $html != "")
    {
        $getAllAccountsJson = json_decode($html);
        if((json_last_error() === JSON_ERROR_NONE))
        {
            $accounts = $getAllAccountsJson->allAccountList;
            $balance = [];
            foreach ($accounts as $account){
                if($account->depositNumber == $accountNumber){
                    $balance['balance'] = str_replace(' ','',(int) $account->balance);
                    $balance['blocked_balance'] = str_replace(' ','',(int) $account->balance - (int) $account->availableBalance);
                    $balance['is_account_blocked'] = false;
                }
            }
            return $balance ?? false;
        }
        else{
//                var_dump("Invalid Json In Balance.");
            return false;
        }
    }else{
//            var_dump("There is an empty response !");
        return false;
    }
}

function getFormattedCurrentDate()
{
    $date = date('Y-m-d h:i:s.000', time());
    $formattedDate = str_replace(' ','T',$date."Z");
    return $formattedDate;
}

function getFormattedPreviousMonthDate()
{
    $date = date('Y-m-d h:i:s.000', time());
    $previousDate = date('Y-m-d h:i:s.000', strtotime($date . ' -1 months'));
    $formattedPreviousDate = str_replace(' ','T',$previousDate."Z");
    return $formattedPreviousDate;

}

function writeOnFile($filePath, $data, $mode = 'w')
{
    file_put_contents($filePath,$data);
}

function notNeedLogout(string $html,$notStrict = true)
{
    $result = json_decode($html);
    if ((json_last_error() === JSON_ERROR_NONE)) {
        if ($result == null || $result == "")
            return $notStrict;
        if (!key_exists('status', (array)$result)) {
            if ($result?->status == 417) {
                return false;
            } else {
                return true;
            }
        }
    }else{
        return $notStrict;
    }
}

function setPayaFormatForSheba($sheba)
{
    if (strlen($sheba) !== 26)
        return false;

    $shebaArray = str_split($sheba);
    $formattedSheba = [];
    foreach ($shebaArray as $index => $char) {
        $formattedSheba[] = $char;

        if (($index + 1) % 4 == 0 && $index > 1)
            $formattedSheba[] = '-';
    }
    return implode($formattedSheba);
}