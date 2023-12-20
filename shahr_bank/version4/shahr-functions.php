<?php

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

    $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $trs = $doc->getElementsByTagName("tr");

    for ($i = 1; $trs->count() > $i; $i++) {
        $descriptions = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent;
        $date = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent;
        $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
        $details = $trs->item($i)->getElementsByTagName("td")->item(8)->textContent;

        preg_match_all('!\d{16}!', $descriptions, $matches);
        $cardNumber = (strpos($descriptions, 'به کارت') !== false && is_array($matches[0]) == true) ? $matches[0][1] : null;

        preg_match_all('!\d*:\d*:\d*!', $details, $matches);
        $hours = (strpos($details, 'ساعت') !== false  && is_array($matches[0]) == true) ? formatTime($matches[0][0]) : "00:00:00";
        $datetime = "$date $hours";
        $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime);

        preg_match_all('!\d{13}!', $details, $matches);
        $erja = (strpos($details, 'مرجع') !== false  && is_array($matches[0]) == true) ? $matches[0][0] : null;

        preg_match_all('!\d{10}!', $details, $matches);
        $sanad = (strpos($details, 'سند') !== false  && is_array($matches[0]) == true) ? $matches[0][0] : null;

        $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, $sanad]);
        if (str_contains($deposit, "-") || $stt != false || $cardNumber == null) { //just deposits, not withdrawals, only first time
            continue;
        } else {
            $result[] = [
                $user_id,//user_id
                $banking_id,//banking_id
                trim(str_replace(',','',$deposit) ?? ''), // amount
                $erja,//erja
                $erja,//peygiri
                $sanad,//serial
                $cardNumber,//card_number
                $datetime,//datetime
                $bigintDatetime,//bigint_datetime
            ];
        }
    }
    return $result;
}

function getBalance(string $html, $currentAccount)
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
        if ($currentAccount == $account) {
            $result = [
                'balance' => str_replace(',','',$balance),
                'blocked_balance' => str_replace(',','',$blocked)
            ];
        }

    }
    return $result;
}

function getInputTag(string $html, string $pattern)
{
    $doc = new DOMDocument();
    preg_match($pattern, $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
    $doc->loadHTML($text);

    $result = null;
    if ($doc->getElementsByTagName("input"))
        $result = $doc->getElementsByTagName("input")[0]->getAttribute("value");
    return $result;
}

function writeOnFile($filePath, $data, $mode = 'w')
{
    file_put_contents($filePath,$data);
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