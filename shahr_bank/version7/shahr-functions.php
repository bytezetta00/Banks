<?php

function getDeposit(string $html, $user_id, $banking_id)
{
    $doc = new DOMDocument();
    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>
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
        if (isset($matches[0])) {
            $cardNumber = (((strpos($descriptions, 'از کارت') !== false || strpos($descriptions,'انتقال وجه از') !== false || strpos($descriptions,"از سپرده") !== false)) && is_array($matches[0]) == true  && empty($matches[0]) == false) ? $matches[0][0] : null;
        }
        if (isset($matches[0])) {
            preg_match_all('!\d*:\d*:\d*!', $details, $matches);
            $hours = (strpos($details, 'ساعت') !== false && is_array($matches[0]) == true && empty($matches[0]) == false) ? formatTime($matches[0][0]) : "00:00:00";
            $datetime = str_replace(["‪", "‬"], "", "$date $hours");
            $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime);
        }

        preg_match_all('!\d{11,13}!', $details, $matches);
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

        $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, trim($sanad)]);
//        if($banking_id == 204){
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
        if (str_contains($deposit, "-") || $stt != false || $cardNumber == null) { //just deposits, not withdrawals, only first time
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

function getBalance(string $html, $currentAccount)
{

    $doc = new DOMDocument();
    preg_match('/<table cellpadding="0" cellspacing="0">(.*?)<\/table>/s', $html, $matches);
    //    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>$matches[0]
    </body></html>";

    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML($text);
    libxml_use_internal_errors($internalErrors);

    $trs = $doc->getElementsByTagName("tr");

    $result = [];
    for ($i = 1; $trs->count() > $i; $i++) {
        $account = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
        $status = $trs->item($i)->getElementsByTagName("td")->item(2)->textContent;
        $total = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent;
        $balance = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
        $blocked = $trs->item($i)->getElementsByTagName("td")->item(5)->textContent;

        $total = (int)str_replace(',', '', $total);
        $available_balance = (int)str_replace(',', '', $balance);
        $blocked = $total - $available_balance;
        if ($currentAccount == $account) {
            $result = [
                'balance' =>  $total,
                'blocked_balance' => $blocked
            ];

            if (strpos($status, "مسدود برداشت") !== false) {
                $result["is_account_blocked"] = true;
            }
//            newLog(var_export($result,true)."\n\n".$status,'shahr-balance-debug');
            return $result;
        }

    }

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

function writeOnFile($filePath, $data, $mode = 'w')
{
    file_put_contents($filePath, $data);
}

function convertToString($text): ?string
{
    $stringText = match (true) {
        (is_string($text) == true) => $text,
        (is_numeric($text) == true) => "$text",
        (is_array($text) == true) => implode("&", array_map(function ($a) {
            return (is_array($a) == true) ? json_encode($a) : $a;
        }, $text)),
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

function convertPersianNumberToEnglish(string $text)
{
    $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishNumber = range(0, 9);
    return str_replace($persianNumber, $englishNumber, $text);
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