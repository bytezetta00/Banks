<? 

// for getting latest account balances
// $accountsStatsUrl = "https://old.abplus.ir/panel/pishkhan/accountsStats";
$accountsStatsUrl = "https://old.abplus.ir/panel/kariz/nextphase/blockaccount";
curl_setopt($ch, CURLOPT_URL, $accountsStatsUrl);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
$accountsStatsResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/accountsStatsResponse.html', $code . $accountsStatsResponse);

$csrfPattern = '/<input type="hidden" name="csrf" id="csrf" value=".*">/';
$csrf = getInputTag($accountsStatsResponse,$csrfPattern);
$getaccountbalanceData = [
    'accountId' => '0302672677006',
    'csrf' => $csrf // '7e22ceec824b21a11795c604c4f7000c'
];
var_dump($getaccountbalanceData);

$getaccountbalanceUrl = "https://old.abplus.ir/panel/tools/getaccountbalance";
curl_setopt($ch, CURLOPT_URL, $getaccountbalanceUrl);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($getaccountbalanceData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    // "Accept: */*", 
    "Accept: application/json", 
    "Content-type: application/json",
    // "Content-type: application/x-www-form-urlencoded",
    'TE' => 'trailers',
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
]);
curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
$getaccountbalanceResponse = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
writeOnFile('responses/getaccountbalanceResponse.html', $code . $getaccountbalanceResponse);