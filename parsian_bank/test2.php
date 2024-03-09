<?php
require_once "./global.php";

$amount = (int) 69000.0;
echo $amount;die;
$text = "رمز ورود به اینترنت بانک یا همراه بانک:
63760
مهلت استفاده دو دقیقه";

if((strpos($text,'رمز ورود به اینترنت بانک یا همراه بانک') !== false)) {
    preg_match('!\d{5}!', $text, $matches);
    if(isset($matches[0])) {
        var_dump($matches[0]);
    }
}
$text1 = "%D8%B1%D9%85%D8%B2 %D8%A7%D9%86%D8%AA%D9%82%D8%A7%D9%84 %D9%88%D8%AC%D9%87 %D9%86%D8%A7%D8%AF%D8%B1%D8%B3%D8%AA %D8%A7%D8%B3%D8%AA.";
$text = "%D8%A7%D9%85%DA%A9%D8%A7%D9%86 %D9%88%D8%B1%D9%88%D8%AF %D8%B4%D9%85%D8%A7 %D8%A8%D9%87 %D8%B3%D9%8A%D8%B3%D8%AA%D9%85 %D9%88%D8%AC%D9%88%D8%AF %D9%86%D8%AF%D8%A7%D8%B1%D8%AF. %D9%84%D8%B7%D9%81%D8%A7 %D8%A8%D8%B9%D8%AF%D8%A7 %D8%A8%D8%B1%D8%A7%D9%8A %D9%88%D8%B1%D9%88%D8%AF %D8%AA%D9%84%D8%A7%D8%B4 %DA%A9%D9%86%D9%8A%D8%AF.";
$out = '%D8%A8%D8%B2%D8%B1%DA%AF-%D8%AA%D8%B1%DB%8C%D9%86-%D9%88%D8%B1%D8%B2%D8%B4%DA%A9%D8%A7%D8%B1%D8%A7%D9%86-%D8%AA%D8%A7%D8%B1%DB%8C%D8%AE-%D8%A7%D9%84%D9%85%D9%BE%DB%8C%DA%A9%D8%AA%D8%B5%D8%A7%D9%88%DB%8C%D8%B1';
// exceptionType
//	%D8%A7%D9%85%DA%A9%D8%A7%D9%86 %D9%88%D8%B1%D9%88%D8%AF %D8%B4%D9%85%D8%A7 %D8%A8%D9%87 %D8%B3%D9%8A%D8%B3%D8%AA%D9%85 %D9%88%D8%AC%D9%88%D8%AF %D9%86%D8%AF%D8%A7%D8%B1%D8%AF. %D9%84%D8%B7%D9%81%D8%A7 %D8%A8%D8%B9%D8%AF%D8%A7 %D8%A8%D8%B1%D8%A7%D9%8A %D9%88%D8%B1%D9%88%D8%AF %D8%AA%D9%84%D8%A7%D8%B4 %DA%A9%D9%86%D9%8A%D8%AF.
var_dump(urldecode($text1));die;

[
    "4001003475683",
    "amin_4560242127._",
    "42838112@Kk"];
