<?php
load('http');
class postbank extends banking
{
    private $account;
    private $username;
    private $password;
    private $user_id;
    private $banking_id;
    private $http;
    public $needs_login_task = true;

    public function __construct($data, $user_id, $banking_id)
    {
        $GLOBALS['account'] = $this->account = @$data['account'];
        $this->username = @$data['username'];
        $this->password = @$data['password'];
        $this->user_id = $user_id;
        $this->banking_id = $banking_id;
        $this->http = new HTTP();
        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0');
        $this->http->setCookieLocation(COOKIE_PATH.'postbank-'.$this->banking_id.'.txt');
        $this->http->setTimeout(50);
        $this->http->setVerbose(true);
    }

    public function setProxy($config) {
        setBankingProxy($config,'postbank',$this->http);
    }

    public function logout()
    {
        unlink(COOKIE_PATH.'postbank-'.$this->banking_id.'.txt');
    }

    public function login()
    {
        if($this->isSignedIn()) {
            return true;
        } else {
            $this->createNewLoginTask($this->banking_id);
            //$GLOBALS['disable_banking'] = 'ورود دو عاملی فعال است';
            return false;
        }
    }

    function isSignedIn()
    {
        $url = 'https://ib.postbank.ir/webbank/viewAcc/viewDetailsAccountHtmlReport.action?currency=IRR&ownership=BE_TANHAYEE&personality=ACTUAL';
        //$url = 'https://ib.postbank.ir/webbank/home/homePage.action';
        $page = $this->http->get($url,'get','','','');
        //@file_put_contents(ROOT_PATH . 'dumps/postbank-issignin.txt', $this->http->getVerboseLog()."\n\n\n================\n\n\n".$page);
        if(strpos($page, $this->account) !== false) {
            $html = str_get_html($page);
            $balance = $html->find('table tr td',4)->find('div',0)->plaintext;
            $GLOBALS['balance'] = str_replace(',','',$balance);
            return true;
        } else {
            return false;
        }
    }

    public function autoSigninStep1()
    {
        unset($_SESSION['postbank_token_'.$this->banking_id]);
        $res = $this->getSigninPage($this->username);
        //file_put_contents(ROOT_PATH.'dumps/postbank-token-debug.txt','token1:'.$res['loginToken']."\n");
        if(!is_array($res)) {
            //خطا در دریافت توکن
            return false;
        }
        if(isset($res['needs_captcha'])) {
            $captcha = decodeCaptcha(UPLOAD_PATH . 'postbank-captcha-' . $this->username . '.jpg', 'postbank');
            $strlen_captcha = strlen($captcha);
            if ($strlen_captcha < 4 || $strlen_captcha > 5) {
                //خطا در تشخیص کپچا، لطفا مجدد امتحان کنید
                return false;
            }
        } else {
            $captcha = '';
        }
        $res1 = $this->doSignin($this->username, $this->password, $res['loginToken'], $captcha);
        //file_put_contents(ROOT_PATH.'dumps/postbank-token-debug.txt','token2:'.$res1['token']."\n",FILE_APPEND);
        if(is_array($res1) && isset($res1['token'])) {
            return $res1;
        } else {
            //خطا در دریافت توکن، لطفا مجدد امتحان کنید
            return false;
        }
    }

    public function getCodeFromSMS($messages,$type=1)
    {
        //file_put_contents(ROOT_PATH.'dumps/postbank-autoSigninStep2.txt','messages:'.var_export($messages,true));
        foreach($messages as $message) {
            if((strpos($message['message'],'پست بانک') !== false) || (strpos($message['message'],'امنيتي') !== false)) {
                preg_match_all('!\d{4}!', $message['message'], $matches);
                if(isset($matches[0][0])) {
                    return $matches[0][0];
                }
            }
        }
        return false;
    }

    public function autoSigninStep2($data,$otp)
    {
        //file_put_contents(ROOT_PATH.'dumps/postbank-token-debug.txt','token3:'.$data['token']."\n",FILE_APPEND);
        if($this->twoPhaseLogin($data['token'], $otp, $data['mobile'])) {
            return true;
        } else {
            return false;
        }
    }

    function getSigninPage($username)
    {
        //$url = 'https://ib.postbank.ir/webbank/login/loginPage.action?ibReq=WEB';
        $url = 'https://ib.postbank.ir/webbank/login/logout.action';
        //$this->get($url,'get');
        $page = $this->get($url,'get');
        @file_put_contents(ROOT_PATH.'dumps/postbank-'.$username.'.txt',$this->http->getVerboseLog()."\n\n\n".$page);
        if(!empty($page)) {
            $html = str_get_html($page);
            $token = $html->find('input[name=loginToken]',0)->value;
            $res = [];
            if(!empty($token)) {
                $res['loginToken'] = $token;
                if(strpos($page,'captchaImage') !== false) {
                    $captchaUrl = 'https://ib.postbank.ir/webbank/login/captcha.action?isSoundCaptcha=false&r='.rand(0,999999999);
                    $captchaImage = $this->get($captchaUrl,'get');
                    file_put_contents(UPLOAD_PATH.'postbank-captcha-'.$username.'.jpg',$captchaImage);
                    //file_put_contents(UPLOAD_PATH.'postbank-captcha-'.$username.'-backup.jpg',$captchaImage);
                    $res['needs_captcha'] = true;
                    return $res;
                } else {
                    return $res;
                }
            } else {
                return 'empty token';
            }
        } else {
            return 'empty page';
        }
    }

    function doSignin($username, $password, $token, $captcha='')
    {
        $ref = 'https://ib.postbank.ir/webbank/login/loginPage.action?ibReq=WEB';
        $url = 'https://ib.postbank.ir/webbank/login/login.action?ibReq=WEB&lang=fa';
        $params = array(
            'struts.token.name' => 'loginToken',
            'loginToken' => $token,
            'otpSyncRequired' => 'false',
            'username' => $username,
            'hiddenPass1' => '1',
            'hiddenPass2' => '2',
            'hiddenPass3' => '3',
            'password' => $password,
            'loginType' => 'STATIC_PASSWORD',
        );
        if(!empty($captcha)) {
            $params['captcha'] = $captcha;
            $params['isSoundCaptcha'] = 'false';
            $params['soundCaptchaEnable'] = 'true';
        }
        //@file_put_contents(ROOT_PATH . 'dumps/postbank-signin-result-'.$username.'.txt', var_export($params,true) . "\n\n\nbefore request\n\n\ncaptcha:".$captcha);
        $res = $this->get($url, 'post', $ref, $params);
        $_SESSION['postbank_doSignin_time'] = time();
        $html = str_get_html($res);
        //@file_put_contents(ROOT_PATH . 'dumps/postbank-signin-result-'.$username.'.txt',"\n\n\n" . $res."\n\n\n".$this->http->getVerboseLog(),FILE_APPEND);
        if($error = $html->find('#errorDiv dd ul li',0)) {
            $error = $error->plaintext;
            if(!empty($error)) {
                $GLOBALS['signin_error'] = $error;
            }
        }
        if(!empty($html)) {
            if($x = $html->find('input[name=ticketLoginToken]', 0)) {
                $token = $x->value;
                if (!empty($token)) {
                    if($mn = $html->find('input[name=mobileNumber]', 0)) {
                        $mn = $mn->value;
                    } else {
                        $mn = '98912***1234';
                    }

                    return ['token' => $token, 'mobile' => $mn];
                }
            }
        }
        //if (!empty($res)) {
        //@file_put_contents(ROOT_PATH . 'dumps/postbank-signin-result-'.$username.'.txt', var_export($params,true) . "\n\n\n" . $res."\n\n\ncaptcha:".$captcha,FILE_APPEND);
        //}
        return false;
    }

    function twoPhaseLogin($token, $ticket, $mobile)
    {
        $url = 'https://ib.postbank.ir/webbank/login/twoPhaseLoginWithTicket.action?ibReq=WEB&lang=fa';
        $ref = 'https://ib.postbank.ir/webbank/login/login.action?ibReq=WEB&lang=fa';
        $time = 120 - (time() - $_SESSION['postbank_doSignin_time']);
        if($time < 0) {
            $time = 0;
        }
        $params = array(
            'struts.token.name' => 'ticketLoginToken',
            'ticketLoginToken' => $token,
            'ticketResendTimerRemaining' => (string)$time,
            'mobileNumber' => $mobile,
            'hiddenPass1' => '1',
            'hiddenPass2' => '2',
            'hiddenPass3' => '3',
            'ticketCode' => $ticket,
        );
        $res = $this->get($url, 'post', $ref, $params);
        $html = str_get_html($res);
        if($error = $html->find('.errorPage #content-title',0)) {
            $error = $error->plaintext;
            if(!empty($error)) {
                $this->logout();
                $GLOBALS['signin_error'] = $error;
                return false;
            }
        }
        return true;
    }

    function logStatements($datetime='null', $amount='null')
    {
        //https://ib.postbank.ir/webbank/viewAcc/billHtmlReport.action?selectedDeposit=0304735933000&desc=&order=DESC&billType=1&paymentId=&
        //fromDateTime=1397%2F07%2F17++-++18%3A21&toDateTime=1397%2F07%2F17++-++22%3A14
        //fromDateTime=1397/07/17  -  18:21&toDateTime=1397/07/17  -  22:14
        load('date');
        $last_log_path = CACHE_PATH.'last-log-postbank-'.$this->account.'.txt';
        $now = strtotime('now');
        $flag = true;
        if($datetime != 'null') {
            $d = explode('-',$datetime);
            $time_from = $d[0].'/'.$d[1].'/'.$d[2].'  -  '.$d[3].':00';
            $time_to = $d[0].'/'.$d[1].'/'.$d[2].'  -  '.$d[3].':59';
            $flag = false;
        } else {
            if (file_exists($last_log_path)) {
                $last_log = @file_get_contents($last_log_path);
                $last_log = explode(',', $last_log);
                $last_log_time = (int)$last_log[0];
                $last_log_serial = $last_log[1];
                if ($last_log_time + 86400 < $now) {
                    $last_log_time = $now - 86400;
                    $last_log_serial = 1;
                }
            } else {
                $last_log_time = $now - 86400;
                $last_log_serial = 2;
            }
            $time_to = strtotime('+5 minutes');
            $time_from = strtotime('-5 minutes', $last_log_time);
            $time_to = jdate('Y/m/d  -  H:i', $time_to);
            $time_from = jdate('Y/m/d  -  H:i', $time_from);
        }
        $results = $this->searchStatements($time_from,$time_to,$amount,'');
        $insert = array();
        if($results !== false) {
            $bigest_serial = 0;
            foreach ($results as $statement) {
                $statement['serial'] = padZeroFromStart($statement['serial']);
                //$statement['bigint_datetime'] = str_replace(['/',' ',':'],[''],$statement['datetime']).'00';
                $stt = DB::getRow('transfer_logs','banking_id=? AND serial=? ORDER BY id DESC',array($GLOBALS['banking_id'],$statement['serial']));
                if(!empty($stt)) {
                    $diff_flag = false;
                    if($stt['amount'] != $statement['amount']) {
                        $diff_flag = true;
                        $type = 'AMOUNT';
                    } elseif($stt['datetime'] != $statement['bigint_datetime']) {
                        $diff_flag = true;
                        $type = 'DATETIME';
                    }
                    if($diff_flag) {
                        $new_serial = $statement['bigint_datetime'] . $statement['serial'];
                        $stt = DB::getRow('transfer_logs','banking_id=? AND serial=? ORDER BY id DESC',array($GLOBALS['banking_id'],$new_serial));
                        if(!$stt) {
                            notifyAdmin('SAME STATEMENT : ' . $type . "\n\n" . var_export($stt, true) . "\n" . var_export($statement, true));
                            $statement['serial'] = $new_serial;
                            $stt = false;
                        }
                    }
                }
                if(!$stt) {
                    $insert[] = array(
                        $this->user_id,
                        $GLOBALS['banking_id'],
                        $statement['amount'],
                        padZeroFromStart($statement['erja']),
                        padZeroFromStart($statement['peygiri']),
                        $statement['serial'],
                        $statement['card_number'],
                        $statement['datetime'],
                        $statement['bigint_datetime'],
                    );
                }
            }
            if(!empty($insert)) {
                if($flag)
                    file_put_contents($last_log_path,$now.','.$bigest_serial);
            } else {
                if($flag)
                    file_put_contents($last_log_path,$now.','.$last_log_serial);
            }
            //$GLOBALS['new_transfers'] = count(@$insert);
            return $insert;
        }
        return false;

    }

    function searchStatements($time_from, $time_to, $exact_amount, $q, $page=1)
    {
        $url = 'https://ib.postbank.ir/webbank/viewAcc/partialDepositShow.action?selectedDeposit='.$this->account.'&desc=&order=DESC&billType=1&fromDateTime='.urlencode($time_from).'&toDateTime='.urlencode($time_to);
        //$url = 'https://ib.postbank.ir/webbank/viewAcc/billHtmlReport.action?selectedDeposit='.$this->account.'&fromDateTime='.urlencode($time_from).'&toDateTime='.urlencode($time_to).'&minAmount=&maxAmount=&desc=&order=DESC&billType=0&paymentId=';
        if($page > 1) {
            $url .= '&page='.$page;
        }
        $pg = $this->get($url,'get');
        file_put_contents(ROOT_PATH.'dumps/postbank-debug.txt',$url."\n\n".$pg);
        file_put_contents(ROOT_PATH.'dumps/postbank-debug-new.txt',"start\n");
        $GLOBALS['statement_debug'] = $pg;
        if(!empty($pg)) {
            $html = str_get_html($pg);
            $table = $html->find('#rowTbl',0);
            if(!empty($table)) {
                $result = [];
                $trs = $table->find('tr[class]');
                foreach ($trs as $tr) {
                    $info = $tr->find('td', 1)->plaintext;
                    $info = convertFaToEn($info); //انتقال از ۶۲۱۹۸۶۱۰۳۸۰۹۳۳۰۷ به ۵۰۲۹۳۸۱۰۲۹۲۸۴۹۷۳ با کدرهگيري ۵۵۶۶۳۷
                    file_put_contents(ROOT_PATH.'dumps/postbank-debug-new.txt',$info."\n",FILE_APPEND);
                    preg_match_all('/[\d]{3,}/', $info, $info_matches);
                    preg_match_all('/[\d]{16}/', $info, $card_matches);
                    foreach ($info_matches[0] as $k => $inf) {
                        $info_matches[0][$k] = $inf = padZeroFromStart($inf);
                        if (strlen($inf) <= 4) {
                            unset($info_matches[0][$k]);
                        }
                    }
                    file_put_contents(ROOT_PATH.'dumps/postbank-debug-new.txt',var_export($info_matches,true)."\n".var_export($card_matches,true)."\n",FILE_APPEND);
                    if (count($card_matches[0]) > 1) {
                        $is_card_ok = false;
                        foreach ($info_matches[0] as $k => $inf) {
                            if ($inf == $GLOBALS['card']) {
                                unset($info_matches[0][$k]);
                                $is_card_ok = true;
                                break;
                            }
                        }
                        if (!$is_card_ok) {
                            notifyAdmin('wrong card in statement 
right card : ' . $GLOBALS['card'] . '

' . $info);
                            continue;
                        }
                        $info_type = 2;
                    } else {
                        $info_type = 1;
                    }
                    $card = '';
                    foreach ($info_matches[0] as $k => $inf) {
                        if (strlen($inf) == 16) {
                            $card = $inf;
                            unset($info_matches[0][$k]);
                            break;
                        }
                    }
                    file_put_contents(ROOT_PATH.'dumps/postbank-debug-new.txt','card:'.$card."\n",FILE_APPEND);
                    if (empty($card)) {
                        if(strpos($info,$this->account) !== false) { // account to account
                            // انتقال وجه اينترنتي از سپرده 0104842227001 به سپرده 0304763511009
                            preg_match_all('/[\d]{13}/', $info, $acc_matches);
                            if (count($acc_matches[0]) > 1) {
                                foreach ($info_matches[0] as $k => $inf) {
                                    if ($inf == $this->account) {
                                        unset($info_matches[0][$k]);
                                        break;
                                    }
                                }
                                foreach ($info_matches[0] as $k => $inf) {
                                    if (strlen($inf) == 13) {
                                        $card = $inf;
                                        unset($info_matches[0][$k]);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    if(empty($card)) {
                        notifyAdmin('not specified card 
account ' . $this->account . '

' . $info);
                        continue;
                    }
                    $peygiri = '';
                    if (!empty($info_matches[0])) {
                        foreach ($info_matches[0] as $k => $inf) {
                            if (strlen($inf) > strlen($peygiri)) {
                                $peygiri = $inf;
                            }
                        }
                    }
                    //$id = $tr->find('td', 2)->find('input', 0)->value; //456188848_1539096665000_1
                    //$id = str_replace('_', '', $id);
                    $date = trim(convertFaToEn($tr->find('td', 2)->plaintext)); //‪۱۳۹۷/۰۷/۱۷‬
                    $date = substr($date, 3, 10); // striping one (hidden) char from start and end
                    $amount = trim($tr->find('td', 3)->plaintext);
                    $amount = convertFaToEn(str_replace(',', '', $amount));
                    $extra = trim($tr->find('td', 7)->plaintext);
                    $extra = convertFaToEn($extra);
                    preg_match_all('/[\d:]{3,}/', $extra, $extra_matches);
                    $time = $extra_matches[0][0];
                    $sanad = $extra_matches[0][1];
                    $erja = $extra_matches[0][2];
                    if (empty($peygiri)) {
                        $peygiri = $erja;
                    } elseif(empty($erja)) {
                        $erja = $peygiri;
                    }
                    if (strpos($time, ':') !== false) {
                        $time = explode(':', $time);
                        $hour = $time[0];
                        $minute = $time[1];
                        $second = $time[2];
                        if (strlen($hour) == 1) {
                            $hour = '0' . $hour;
                        }
                        if (strlen($minute) == 1) {
                            $minute = '0' . $minute;
                        }
                        if (strlen($second) == 1) {
                            $second = '0' . $second;
                        }
                        $date = explode('/', $date);
                        $datetime = $date[0] . '/' . $date['1'] . '/' . $date[2] . ' ' . $hour . ':' . $minute . ':' . $second;
                        $id = $sanad.$amount;
                        $result[] = array(
                            'datetime' => $datetime,
                            'bigint_datetime' => $date[0] . $date[1] . $date[2] . $hour . $minute . $second,
                            'description' => $info,
                            'serial' => $id,
                            'amount' => (int)$amount,
                            'card_number' => $card,
                            'erja' => $erja,
                            'peygiri' => $peygiri,
                        );
                        file_put_contents(ROOT_PATH.'dumps/postbank-debug-new.txt',"new result:".$amount.'-'.$card.'-'.$datetime.'-'.$id.'-'.$erja.'-'.$peygiri."\n",FILE_APPEND);
                    } else {
                        notifyAdmin('time detection failed in postbank bank ' . $this->account . '
text :
' . $extra . '

detected time :
' . $time);
                    }

                }
                //file_put_contents(ROOT_PATH.'dumps/postbank-debug-new.txt',"not skipped\n",FILE_APPEND);
                // check if there's a next page
                if ($html->find('#paging a img#next', 0)) {
                    // load other statements
                    $page++;
                    $nextPage_result = $this->searchStatements($time_from, $time_to, $exact_amount, $q, $page);
                    $result = array_merge($result, $nextPage_result);
                }
                $result = array_reverse($result);
                return $result;
            } else {
                $GLOBALS['statement_debug'] = 'no statement';
                return [];
            }
        } else {
            return false;
        }
    }

    public function keepSession()
    {
        $url = 'https://ib.postbank.ir/webbank/home/homePage.action';
        $pg = $this->get($url,'get');
    }

    public function getCardHolder($card, $data)
    {
        return getCardHolder($card,$data);
    }

    private function getBalances()
    {
        $url = 'https://ib.postbank.ir/webbank/viewAcc/viewDetailsAccountHtmlReport.action?currency=IRR&ownership=BE_TANHAYEE&personality=ACTUAL';
        $page = $this->http->get($url,'get','','','');
        //file_put_contents(ROOT_PATH.'dumps/postbank-balance.txt',$this->http->getVerboseLog()."\n\n\n".$page);
        if(strpos($page, $this->account) !== false) {
            $html = str_get_html($page);
            $trs = $html->find('tbody tr');
            if($trs !== false) {
                foreach ($trs as $tr) {
                    $acc = $tr->find('td',1)->find('div',0)->plaintext;
                    if($acc == $this->account) {
                        $balance = str_replace(',','',$html->find('table tr td',3)->find('div',0)->plaintext);
                        $a_balance = str_replace(',','',$html->find('table tr td',4)->find('div',0)->plaintext);
                        $b_balance = str_replace(',','',$html->find('table tr td',5)->find('div',0)->plaintext);;
                        $blocked_balance = $balance - $a_balance;
                        if($b_balance > $blocked_balance) {
                            $blocked_balance = $b_balance;
                        }
                        return [
                            'balance' => $balance,
                            'blocked_balance' => $blocked_balance,
                        ];
                    }
                }
            }
        }
        return false;
    }

    public function payaTransferStep1($sheba,$name,$amount,$note)
    {
        try {
            $url = 'https://ib.postbank.ir/webbank/transfer/newNormalAch.action';
            $ref = 'https://ib.postbank.ir/webbank/chooseSubMenu.action?actionParameter=transfer';
            $res = $this->get($url, 'get', $ref);
            $html = str_get_html($res);
            $token = $html->find('input[name=normalAchTransferToken]', 0)->value;
            $params = [
                'transferType' => 'NORMAL_ACH',
                'struts_token_name' => 'normalAchTransferToken',
                'normalAchTransferToken' => $token,
                'sourceSaving' => $this->account,
                'sourceSavingValueType' => 'sourceDeposit',
                'sourceSavingPinnedDeposit' => '',
                'sourceSavingIsComboValInStore' => 'false',
                'destinationIbanNumber' => $sheba, //'IR380210000001000217783900'
                'destinationIbanNumberValueType' => '',
                'destinationIbanNumberPinnedDeposit' => '',
                'destinationIbanNumberIsComboValInStore' => 'false',
                'owner' => $name,
                'amount' => number_format($amount, 0, '', '٫'),//'100٫000',
                'currency' => 'IRR',
                'currencyDefaultFractionDigits' => '2',
                'reason' => 'DRPA',
                'factorNumber' => '',
                'remark' => $note,
            ];
            $url2 = 'https://ib.postbank.ir/webbank/transfer/normalAchTransfer.action';
            $res2 = $this->get($url2, 'post', $url, $params);
            $html2 = str_get_html($res2);
            $realOwner = $html2->find('input[id=owner]', 0)->value;
            $csrf = $html2->find('meta[name=CSRF_TOKEN]', 0)->content;
            $token2 = $html2->find('input[name=normalAchTransferConfirmToken]', 0)->content;
            $params2 = [
                'CSRF_TOKEN' => $csrf,
                'ticketAmountValue' => $amount,
                'ticketModernServiceType' => 'NORMAL_ACH_TRANSFER',
                'ticketParameterResourceType' => 'DEPOSIT',
                'ticketParameterResourceValue' => $this->account,
                'ticketParameterDestinationType' => 'IBAN',
                'ticketParameterDestinationValue' => $sheba,
                'ticketDestinationName' => $realOwner,
                'ticketAdditionalInfoAmount' => '',
            ];
            $url3 = 'https://ib.postbank.ir/webbank/general/generateTicket.action?' . http_build_query($params2);
            $res3 = $this->get($url3, 'ajaxGet', $url2);
            if (isJson($res3)) {
                $res3 = json_decode($res3, true);
                if (isset($res3['resultType']) && $res3['resultType'] == true) {
                    return [
                        'token' => $token2,
                        'owner' => $realOwner,
                    ];
                }
            }
        } catch(Exception $e) {
            // error
        }
        return false;
    }

    public function payaTransferStep2($data,$ticket,$sheba,$name,$amount,$note)
    {
        $params = [
            'struts_token_name' => 'normalAchTransferConfirmToken',
            'normalAchTransferConfirmToken' => $data['token'],
            'transferType' => 'NORMAL_ACH',
            'sourceSaving' => $this->account,
            'destinationIbanNumber' => $sheba,
            'owner' => $data['owner'],
            'amount' => number_format($amount, 0, '', '٫'),//'100٫000',
            'currency' => 'IRR',
            'reason' => 'DRPA',
            'factorNumber' => '',
            'remark' => $note,
            'hiddenPass1' => '1',
            'hiddenPass2' => '2',
            'hiddenPass3' => '3',
            'ticketRequired' => 'true',
            'ticketResendTimerRemaining' => '15',
            'ticket' => $ticket,
            'back' => 'back',
            'perform' => 'ثبت انتقال وجه',
        ];
        $url = 'https://ib.postbank.ir/webbank/transfer/normalAchTransfer.action';
        $res = $this->get($url,'post',$url,$params);
        $html = str_get_html($res);
        $error = $html->find('.errorContainer .errors');
        if(!empty($error)) {
            $error = $error[0]->text();
        } else {

        }
    }

    private function get($url, $method, $ref='', $params='', $extraHeaders=[])
    {
        if($method == 'get') {
            /*$header = array(
                'content-type: application/x-www-form-urlencoded;',
                'x-requested-with: XMLHttpRequest',
            );*/
            $header = [];
        } elseif($method == 'ajaxGet') {
            if(!empty($extraHeaders)) {
                $header = $extraHeaders;
            }
            $header[] = 'x-requested-with: XMLHttpRequest';
        } else {
            $header = array(
                'x-requested-with: XMLHttpRequest',
            );
            if(is_array($params)) {
                $params = http_build_query($params);
                $header[] = 'content-type: application/x-www-form-urlencoded;';
            } else {
                $header[] = 'content-type: application/json;charset=UTF-8';
            }
        }
        $this->http->setHeaders($header);
        return $this->http->get($url,$method,$ref,$params,'');
    }
}
