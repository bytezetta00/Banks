<?php
require_once('simple_html_dom.php');
class HTTP
{
    private $tunnel = '';
    private $headers;
    private $timeout = 62;
    private $curlConfigs;
    private $cookiePath;
    public $useCookies = true;
    private $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.87 Safari/537.35';
    private $lastCH;
    private $verbose = false;
    private $verboseLog;
    public $use_proxy = false;
    public $status_code;
    private $proxy_ip;
    private $proxy_port;
    private $proxy_user;
    private $proxy_pass;
    private $lastURL;
    private $last_response_headers='';

    function get($url, $method='get', $referer='', $params='', $return='dom')
    {
        $header = [];
        if(is_array($this->headers)) {
            foreach($this->headers as $ch) {
                $header[] = $ch;
            }
        }
        if(!empty($this->tunnel)) {
            // use tunnel
            $p = [
                'type' => $method,
                'url' => $url,
                'referer' => $referer,
                'params' => $params,
                'header' => implode('|', $header),
            ];
            $params = $p;
            $url = $this->tunnel;
            $method = 'post';
            //$this->headers = '';
        } else {

        }

        $this->lastCH = $c = curl_init();
        set_time_limit($this->timeout+10);
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        if(!isset($this->curlConfigs[CURLOPT_FOLLOWLOCATION])) {
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        }
        if($method == 'post') {
            curl_setopt($c,CURLOPT_POST, true);
            curl_setopt($c,CURLOPT_POSTFIELDS, $params);
        } elseif($method == 'patch') {
            curl_setopt($c,CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($c,CURLOPT_POST, true);
            curl_setopt($c,CURLOPT_POSTFIELDS, $params);
        }
        if(!empty($referer)) {
            curl_setopt($c, CURLOPT_REFERER, $referer);
        }
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT_MS, $this->timeout*1000);
        curl_setopt($c, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($c, CURLOPT_TIMEOUT_MS, $this->timeout*1000);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        if(!empty($this->userAgent)) {
            curl_setopt($c, CURLOPT_USERAGENT, $this->userAgent);
        }

        curl_setopt($c, CURLOPT_HEADER, 1);

        if(!empty($this->curlConfigs)) {
            foreach (@$this->curlConfigs as $configID => $configVal) {
                curl_setopt($c, $configID, $configVal);
            }
        }

        if(!empty($this->cookiePath)) {
            //curl_setopt($c, CURLOPT_COOKIEJAR, $this->cookiePath);
            if(!empty($this->tunnel)) {
                $cookies = $this->getCookiesFromFile();
                file_put_contents('dumps/test-sep-cookies.txt',"xxx\n".$cookies."\n\n\n".$this->cookiePath);
                curl_setopt($c, CURLOPT_COOKIE, $cookies);
            } else {
                if($this->useCookies) {
                    curl_setopt($c, CURLOPT_COOKIEFILE, $this->cookiePath);
                }
                curl_setopt($c, CURLOPT_COOKIEJAR, $this->cookiePath);
            }
        }

        if(!empty($this->headers) && empty($this->tunnel)) {
            curl_setopt($c, CURLOPT_HTTPHEADER, $this->headers);
        }

        if($this->verbose) {
            curl_setopt($c, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($c, CURLOPT_STDERR, $verbose);
        }

        if($this->use_proxy) {
            curl_setopt($c, CURLOPT_PROXY,$this->proxy_ip.':'.$this->proxy_port);
            if(!empty($this->proxy_user)) {
                curl_setopt($c, CURLOPT_PROXYUSERPWD, $this->proxy_user . ':' . $this->proxy_pass);
            }
        }

        $res = curl_exec($c);

        $this->lastURL = curl_getinfo($c,CURLINFO_EFFECTIVE_URL);

        $this->status_code = curl_getinfo($c, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
        $this->last_response_headers = $header = trim(substr($res, 0, $header_size));
        file_put_contents(ROOT_PATH.'dumps/http-last-req-headers.txt',$header);
        $body = substr($res, $header_size);

        if(!empty($this->tunnel)) {
            $lines = explode(PHP_EOL, $header);
            foreach ($lines as $line) {
                if(strpos($line,'Set-Cookie') !== false) {
                    $line = substr($line,12);
                    $cookies = parse_cookies($line);
                    foreach ($cookies as $cookie) {
                        if(empty($cookie->expires)) {
                            $expires = 0;
                        } else {
                            $expires = strtotime($cookie->expires);
                        }
                        file_put_contents($this->cookiePath, "\n$cookie->domain\tTRUE\t/\tFALSE\t$expires\t$cookie->name\t$cookie->value", FILE_APPEND);
                    }
                } else {
                    continue;
                }
                /*$line = trim($line);
                if(!empty($line)) {
                    header($line);
                }*/
            }
        }

        if($this->verbose) {
            rewind($verbose);
            $this->verboseLog = stream_get_contents($verbose);
        }
        if($return == 'dom') {
            $html = str_get_html($body);
            return $html;
        } else {
            return $body;
        }
    }

    function getResponseHeaders()
    {
        $headers = [];
        $headersTmpArray = explode("\r\n", $this->last_response_headers );
        $c = count($headersTmpArray);
        for ($i = 0 ; $i < $c ; ++$i) {
            if (strlen($headersTmpArray[$i]) > 0) {
                if(strpos($headersTmpArray[$i], ":" )) {
                    $headerName = substr($headersTmpArray[$i], 0, strpos($headersTmpArray[$i], ":"));
                    $headerValue = substr($headersTmpArray[$i], strpos($headersTmpArray[$i], ":")+1);
                    $headers[$headerName] = $headerValue;
                }
            }
        }
        return $headers;
    }

    function getLastURL()
    {
        return $this->lastURL;
    }

    function getHtml($str)
    {
        $html = str_get_html($str);
        return $html;
    }

    function getInfo($info)
    {
        return curl_getinfo($this->lastCH,$info);
    }

    function setVerbose($verbose) {
        $this->verbose = $verbose;
    }

    function getVerboseLog() {
        return $this->verboseLog;
    }

    function find($html, $selector, $index=null) {
        $res = $html->find($selector);
        if($index !== null) {
            if(isset($res[$index])) {
                $res = $res[$index];
            } else {
                return false;
            }
        }
        return $res;
    }

    function getValue($html, $selector, $index=null) {
        if($obj = $this->find($html, $selector, $index)) {
            return @$obj->value;
        } else {
            return false;
        }
    }

    function getPlaintext($html, $selector, $index=null) {
        if($obj = $this->find($html, $selector, $index)) {
            return @$obj->plaintext;
        } else {
            return false;
        }
    }

    function formToArray($form)
    {
        $inputs = $this->find($form,'input,select');
        $res = [];
        foreach($inputs as $input) {
            if($input->value !== false) {
                $res[$input->name] = @$input->value;
            } else {
                $res[$input->name] = '';
            }
        }
        return $res;
    }

    function tableToArray($table)
    {
        $trs = $this->find($table,'tr');
        $res = [];
        $i = 0;
        foreach($trs as $tr) {
            $tds = $this->find($tr,'td');
            if(!empty($tds)) {
                foreach ($tds as $td) {
                    $res[$i][] = trim($td->plaintext);
                }
                $i++;
            }
        }
        return $res;
    }

    function setUserAgent($ua)
    {
        $this->userAgent = $ua;
    }

    function setTunnel($url)
    {
        $this->tunnel = $url;
    }

    function setProxy($ip,$port,$user='',$pass='')
    {
        $this->proxy_ip = $ip;
        $this->proxy_port = $port;
        $this->proxy_user = $user;
        $this->proxy_pass = $pass;
        $this->use_proxy = true;
    }

    function getProxy()
    {
        if($this->use_proxy) {
            return $this->proxy_ip.':'.$this->proxy_port.':'.$this->proxy_user.':'.$this->proxy_pass;
        } else {
            return false;
        }
    }

    function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    function getHeaders()
    {
        return $this->headers;
    }

    function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    function setCurlConfigs($curlConfigs)
    {
        $this->curlConfigs = $curlConfigs;
    }

    function setCookieLocation($path)
    {
        $this->cookiePath = $path;
    }

    function getCookiesFromFile()
    {
        $res = [];
        $unparsed_cookies = file_get_contents($this->cookiePath);
        $lines = explode(PHP_EOL, $unparsed_cookies);
        foreach ($lines as $line) {
            $cookie = array();
            // detect httponly cookies and remove #HttpOnly prefix
            if (substr($line, 0, 10) == '#HttpOnly_') {
                $line = substr($line, 10);
                $cookie['httponly'] = true;
            } else {
                $cookie['httponly'] = false;
            }

            // we only care for valid cookie def lines
            if(substr($line,0,1) != '#' && substr_count($line, "\t") == 6) {
                // get tokens in an array
                $tokens = explode("\t", $line);
                // trim the tokens
                $tokens = array_map('trim', $tokens);
                // Extract the data
                $cookie['domain'] = $tokens[0]; // The domain that created AND can read the variable.
                $cookie['flag'] = $tokens[1];   // A TRUE/FALSE value indicating if all machines within a given domain can access the variable.
                $cookie['path'] = $tokens[2];   // The path within the domain that the variable is valid for.
                $cookie['secure'] = $tokens[3]; // A TRUE/FALSE value indicating if a secure connection with the domain is needed to access the variable.
                $cookie['expiration-epoch'] = $tokens[4];  // The UNIX time that the variable will expire on.
                $cookie['name'] = urldecode($tokens[5]);   // The name of the variable.
                $cookie['value'] = urldecode($tokens[6]);  // The value of the variable.
                // Convert date to a readable format
                $cookie['expiration'] = date('Y-m-d h:i:s', $tokens[4]);
                // Record the cookie.
                $cookies[] = $cookie;

                $res[] = $cookie['name'].'='.$cookie['value'];
            }
        }

        return implode('; ',$res);
    }
}

function parse_cookies($header) {

    $cookies = array();

    $cookie = new cookie();

    $parts = explode("=",$header);
    for ($i=0; $i< count($parts); $i++) {
        $part = $parts[$i];
        if ($i==0) {
            $key = $part;
            continue;
        } elseif ($i== count($parts)-1) {
            $cookie->set_value($key,$part);
            $cookies[] = $cookie;
            continue;
        }
        $comps = explode(" ",$part);
        $new_key = $comps[count($comps)-1];
        $value = substr($part,0,strlen($part)-strlen($new_key)-1);
        $terminator = substr($value,-1);
        $value = substr($value,0,strlen($value)-1);
        $cookie->set_value($key,$value);
        if ($terminator == ",") {
            $cookies[] = $cookie;
            $cookie = new cookie();
        }

        $key = $new_key;
    }
    return $cookies;
}

class cookie {
    public $name = "";
    public $value = "";
    public $expires = "";
    public $domain = "";
    public $path = "";
    public $secure = false;

    public function set_value($key,$value) {
        switch (strtolower($key)) {
            case "expires":
                $this->expires = $value;
                return;
            case "domain":
                $this->domain = trim($value);
                return;
            case "path":
                $this->path = $value;
                return;
            case "secure":
                $this->secure = ($value == true);
                return;
        }
        if ($this->name == "" && $this->value == "") {
            $this->name = $key;
            $this->value = $value;
        }
    }
}
