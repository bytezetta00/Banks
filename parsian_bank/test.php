<?php
require_once "./global.php";
//238327
//238190
//237219
//45647
// Define the characters to choose from
$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
//abcdefghijklmnopqrstuvwxyz
// Get the length of the characters string
$length = strlen($characters);

// Generate all possible combinations of three characters
$possibilities = [];
for ($i = 0; $i < $length; $i++) {
    for ($j = 0; $j < $length; $j++) {
        for ($k = 0; $k < $length; $k++) {
            $combine = $characters[$i] . $characters[$j] . $characters[$k];
            if(!in_array($combine,$possibilities) && !preg_match('!\d{3}!',$combine,$matches)){
                $possibilities[] = $combine;
            }
            //echo $characters[$i] . $characters[$j] . $characters[$k] . PHP_EOL;
        }
    }
}
echo "count: " . count($possibilities);
$url = "https://www.theswiftcodes.com/swift-code-checker/";
$failedText = "These details don't look right";

$branches = [];
$counter = 0;
foreach ($possibilities as $possibility){
    $html = curlRequest($url,['swift' => "INJSAM22$possibility"]);
    echo "$possibility : $counter".PHP_EOL;
    $html = curlRequest($url,['swift' => "INJSAM22$i"]);
    $counter ++;
    if(!str_contains($html['body'],$failedText)){
        $branches[] = $i;
    }
}
var_dump($branches);
die;
for($i = 0;$i<2;$i++){
    $i = str_pad($i, 3, '0', STR_PAD_LEFT);
    if($i == 0){
        $i = null;
    }
    $html = curlRequest($url,['swift' => "INJSAM22$i"]);
    echo $i.PHP_EOL;
    if(!str_contains($html['body'],$failedText)){
        $branches[] = $i;
    }
}
var_dump($branches);
die;
$text = "%D8%B1%D9%85%D8%B2 %D8%A7%D9%86%D8%AA%D9%82%D8%A7%D9%84 %D9%88%D8%AC%D9%87 %D9%86%D8%A7%D8%AF%D8%B1%D8%B3%D8%AA %D8%A7%D8%B3%D8%AA.";
$out = '%D8%A8%D8%B2%D8%B1%DA%AF-%D8%AA%D8%B1%DB%8C%D9%86-%D9%88%D8%B1%D8%B2%D8%B4%DA%A9%D8%A7%D8%B1%D8%A7%D9%86-%D8%AA%D8%A7%D8%B1%DB%8C%D8%AE-%D8%A7%D9%84%D9%85%D9%BE%DB%8C%DA%A9%D8%AA%D8%B5%D8%A7%D9%88%DB%8C%D8%B1';

var_dump(utf8_encode($text));die;
//
//$text = '{"transactionDate":1708423937224,"trackingCode":"140212010543000000393","transactionId":"BKPA14021201134217223000000393","receiverBankName":"بانک شهر","statusDescription":"انتظار","statusCode":"PEND"}
//';
////$text = '{"timestamp":1708423318757,"status":417,"error":"Expectation Failed","message":"Error","path":"/account/polFundTransfer"}
////';
//$jsonText = json_decode($text,true);
//if(array_key_exists('statusCode' , $jsonText)){
//    if($jsonText["statusCode"] == "PEND"){
//        var_dump($jsonText["statusCode"]);
//        var_dump($jsonText["transactionId"]);
//        var_dump($jsonText["transactionDate"]);
//        //call next url
//    }
//    if($jsonText["statusCode"] == "ACCP"){
//        var_dump($jsonText["statusCode"]);
//        //return final result
//    }
//}
//else if (array_key_exists('error' , $jsonText)){
//    var_dump($jsonText["error"].': '.$jsonText["message"] ?? null);
//}
//else{
//    var_dump('Unknown Error !!');
//}
//var_dump(array_key_exists('statusCode' , $jsonText));
//die;
$text = '{
    "polEntries":
		[{
            "polEntry":
				{
                    "transactionStatus":"ChStatusCodesBean(description=موفق, id=1, stCode=ACCP)","count":null,"totalAmount":10000
				},
			"referenceId":"140212010543000000393",
			"transactionId":"BKPA14021201134217223000000393",
			"status":"ACCP",
			"statusDescription":"موفق",
			"confirmExpireDate":null,
			"sourceDepositNumber":"47001508868601",
			"destinationIban":"IR580610000004001003238852",
			"purposeName":"کمک های نقدی و خیریه",
			"registerDate":1708436537224,
			"totalDebitAmount":10000
		}],
	"totalRecords":1
	}';

$jsonText = json_decode($text,true);
var_dump($jsonText['polEntries'][0]['status']);
die;
$sheba = "IR440610000004001003196133";

var_dump(setPayaFormatForSheba($sheba));
//function setPayaFormatForSheba($sheba): bool|string
//{
//    if (strlen($sheba) !== 26)
//        return false;
//
//    $shebaArray = str_split($sheba);
//    $formattedSheba = [];
//    foreach ($shebaArray as $index => $char) {
//        $formattedSheba[] = $char;
//
//        if (($index + 1) % 4 == 0 && $index > 1)
//            $formattedSheba[] = '-';
//    }
//    return implode($formattedSheba);
//}

die;
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

function curlRequest(string $url, $data = NULL, $headers = [], $proxy = null, $proxyuserpwd = null, $cookieFile = null, $userPass = null)
{
    // echo "geting data from URL:$url";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // curl_setopt($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    // curl_setopt($ch, CURLOPT_ENCODING, 'identity');

    $resHeaders = [];
    // this function is called by curl for each header received
    curl_setopt(
        $ch,
        CURLOPT_HEADERFUNCTION,
        function ($curl, $header) use (&$resHeaders) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) // ignore invalid headers
                return $len;

            $resHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

            return $len;
        }
    );


    if ($userPass) {
        curl_setopt($ch, CURLOPT_USERPWD, $userPass);
    }

    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if (!empty($proxy)) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
    }

    if (!empty($proxyuserpwd)) {
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuserpwd);
    }

    $response = curl_exec($ch);
    if (curl_error($ch)) {
        trigger_error('Curl Error:' . curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        "body" => $response,
        "headers" => $resHeaders,
        "code" => $code
    ];
}