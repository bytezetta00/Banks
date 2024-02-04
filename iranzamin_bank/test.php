<?php
$file = fopen('responses/defaultBillListResponse.html','r');
$data = fread($file , 5000000);
fclose($file);

//$this->loginData['loginToken'] = getInputTag($html, '/<input type="hidden" name="loginToken" value=".*/') ?? '';
$struts = getInputTag($data, '/<input type="hidden" name="struts.token.name" value=".*/');
$advancedSearch = getInputTag($data, '/<input type="hidden" class="" name="advancedSearch" id="advancedSearch" value=".*/') ?? true;
$accountType = getInputTag($data, '/<input type="hidden" class="" name="accountType" id="accountType" value=".*/') ?? "PASANDAZ1";
$maxLenForNote = getInputTag($data, '/<input type="hidden" class="" name="maxLenForNote" id="maxLenForNote" value=".*/') ?? "200";
$selectedDepositIsComboValInStore = getInputTag($data, '/<input type="hidden" class="" name="selectedDepositIsComboValInStore" id="selectedDepositIsComboValInStore" value=".*/') ?? false;
$currency_selectedDeposit = getInputTag($data, '/<input type="hidden" class="" name="currency" id="currency_selectedDeposit" value=".*/') ?? 'IRR';
$currencyDefaultFractionDigits = getInputTag($data, '/<input type="hidden" class="" name="currencyDefaultFractionDigits" id="currencyDefaultFractionDigits_selectedDeposit" value=".*/') ?? 2;
$stmtIdnote1 = getInputTag($data, '/<input type="hidden" class="" name="stmtIdnote1" id="stmtIdnote1" value=".*/') ?? "10635224_1706197421000_3416_1";

//var_dump(getBalance($data,'3416-701-2128111-1'));

var_dump($advancedSearch);die;
function getBalance(string $html,$account)
{
    $doc = new DOMDocument();

    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>
    $matches[0]
    </body></html>";

    $text = convertPersianNumberToEnglish($text);

    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML($text);
    libxml_use_internal_errors($internalErrors);
    $trs = $doc->getElementsByTagName("tr");

    $result = false;
    for ($i = 2; $i < $trs->count(); $i++) {
        $accountNumber = $trs->item($i)->getElementsByTagName("td")->item(0)->textContent;
        if (strpos(setPersianFormatForBalance($accountNumber), $account) != false) {
            $balance = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(1)->textContent);
            $availableBalance = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(2)->textContent);
            $blocked = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(4)->textContent);
            $status = setPersianFormatForBalance($trs->item($i)->getElementsByTagName("td")->item(3)->textContent);

            $balance = (int)str_replace(',', '', $balance);
            $availableBalance = (int)str_replace(',', '', $availableBalance);
            $blockedBalance = $balance - $availableBalance;
            $result = [
                'balance' => $balance,
                'blocked_balance' => $blockedBalance
            ];
            if (strpos($status, "مسدود برداشت") !== false) {
                $result["is_account_blocked"] = true;
            }
//            newLog(var_export($result,true)."\n\n".$status,'sina-balance-debug','sina');

            return $result;
        }
        return $result;
    }
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

function getInputTag(string $html, string $pattern)
{
    $doc = new DOMDocument();
    preg_match($pattern, $html, $matches);
    if(!isset($matches[0]))
        return false;

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