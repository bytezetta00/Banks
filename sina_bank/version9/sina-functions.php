<?php

function getDeposits(string $html,$user_id, $banking_id)
{
    $doc = new DOMDocument();
    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><body>
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

        if(isset($erja,$sanad,$datetime)) {
            $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, trim($sanad)]);

            if (str_contains($deposit, "-") || $stt != false || $cardNumber == null) {
                continue;
            } else {
                $result[] = [
                    $user_id, // user_id
                    $banking_id, // banking_id
                    trim(str_replace(',', '', $deposit) ?? ''), // amount
                    trim($erja ?? ''), // erja
                    trim($erja ?? ''), // peygiri
                    trim($sanad ?? ''), // serial
                    trim($cardNumber ?? ''), // card_number
                    $datetime, // datetime
                    $bigintDatetime, // bigint_datetime
                ];
            }
        }
    }
    return $result;
}
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
    if($trs->item(2)) {
        $accountNumber = $trs->item(2)->getElementsByTagName("td")->item(1)->textContent;
        $balance = setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(1)->textContent);
        $availableBalance = setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(2)->textContent);
        $blocked = setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(4)->textContent);

        return [
            'balance' => str_replace(',', '', $balance),
            //'availableBalance' => $availableBalance,
            'blocked_balance' => str_replace(',', '', $blocked)
        ];
    } else {
        return false;
    }
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

function writeOnFile($filePath, $data, $mode = 'w')
{
    file_put_contents($filePath,$data);
}

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

function convertPersianNumberToEnglish(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    return str_replace($persianNumber, $englishNumber, $text);
}

function convertToString($text):?string
{
    $stringText = match (true) {
        (is_string($text) == true) => $text,
        (is_numeric($text) == true) => "$text",
        (is_array($text) == true) => implode("&",array_map(function($a) {return (is_array($a) == true) ? json_encode($a) : $a;},$text)),
        (is_bool($text) == true) => ($text) ? "true" : "false",
        default => var_export($text),
    };
    return $stringText;
}

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
