<?php 

function getDeposit(string $html) 
{
    $doc = new DOMDocument();
    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s', $html, $matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";

    $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $trs = $doc->getElementsByTagName("tr");

    for ($i = 1; $trs->count() > $i; $i++) {
        $deposit = $trs->item($i)->getElementsByTagName("td")->item(4)->textContent;
        if (str_contains($deposit, "-")) { //just deposits, not withdrawals
            continue;
        } else {
            $result[] = $deposit;
        }

    }
    return $result;
}

function getBalanceFromDeposit(string $html){
    $doc = new DOMDocument();
    preg_match('/<table class="datagrid" id="rowTbl">(.*?)<\/table>/s',$html,$matches);
    $text = "<html><body>
    $matches[0]
    </body></html>";
    $doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $trs = $doc->getElementsByTagName("tr");
    return $trs->item(1)->getElementsByTagName("td")->item(6)->textContent;
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
    $captchaFile = fopen($filePath,$mode);
    fwrite($captchaFile,$data);
    fclose($captchaFile);
}