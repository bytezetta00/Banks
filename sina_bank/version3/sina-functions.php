<?php

function getDeposits(string $html,$user_id, $banking_id)
{
    $doc = new DOMDocument();
    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
    $text = convertPersianNumberToEnglish($text);
    $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $trs = $doc->getElementsByTagName("tr");
    for ($i = 2; $trs->count() > $i; $i++) {
        $details = $trs->item($i)->getElementsByTagName("td")->item(1)->textContent; // this is "sharh" it has bunch of data, we need
        $date = $trs->item($i)->getElementsByTagName("td")->item(3)->textContent; // date
        $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent; // deposit 
        $description = $trs->item($i)->getElementsByTagName("td")->item(8)->textContent; // shomareh sanad(document id) & saat(hour-time)

        preg_match_all('!\d*:\d*:\d*!', $description, $matches);
        $hour = (strpos($description, 'ساعت') !== false) ? $matches[0][0] : "00:00:00";
        $datetime = "$date $hour";
        $bigintDatetime = str_replace(['/', ':', ' '], '', $datetime);

        preg_match_all('! \d{8}!', $description, $matches);
        $sanad = (strpos($description, 'سند') !== false) ? $matches[0][0] : null;

        preg_match_all('! \d{12} !', $details, $matches);
        $erja = (strpos($details, 'ش م') !== false) ? $matches[0][0] : null;

        preg_match_all('! \d{16}!', $details, $matches);
        $cardNumber = (strpos($details, 'از ک') !== false) ? $matches[0][0] : null;

        $stt = DB::getRow('transfer_logs', 'banking_id=? AND serial=?', [$banking_id, $sanad]);

        if (str_contains($deposit, "-") || $stt != false) {
            continue;
        } else {
            $result[] = [
                'user_id' => $user_id,
                'banking_id' => $banking_id,
                'amount' => trim($deposit),
                'erja' => trim($erja),
                'peygiri' => trim($erja),
                'serial' => trim($sanad),
                'card_number' => trim($cardNumber),
                'datetime' => $datetime,
                'bigint_datetime' => $bigintDatetime,
            ];
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

    $doc->loadHTML($text);
    $trs = $doc->getElementsByTagName("tr");
    $accountNumber = $trs->item(2)->getElementsByTagName("td")->item(1)->textContent;
    $balance = setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(1)->textContent);
    $availableBalance = setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(2)->textContent);
    $blocked = setPersianFormatForBalance($trs->item(2)->getElementsByTagName("td")->item(4)->textContent);

    return [
        'balance' => $balance, 
        'availableBalance' => $availableBalance, 
        'blocked' => $blocked
    ];
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