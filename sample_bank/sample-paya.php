<?php
class Paya
{
    public function payaTransfer($iban, $amount, $name, $surname, $desc = '')
    {
        $url = 'https://ib.postbank.ir/netway/api/transferMoney/inquiry';
        $ref = 'https://ib.postbank.ir/netway/home';
        $params = '{"data":{"destination":"' . $iban . '","destinationType":"IBAN"},"context":{"data":[{"key":"CurrentUserType","value":"OWNER"},{"key":"CurrentUserId","value":"' . @$this->data['CurrentUserId'] . '"},{"key":"language","value":"fa"}]}}';
        $res = $this->call($url, $ref, $params);
        if (isset($res['destinationIsValid']) && $res['destinationIsValid'] == true) {
            $ownerName = $res['ownerName'];
            $bankName = $res['bankName'];
            $params2 = '{"data":{"serviceName":"ACH_NORMAL_TRANSFER","resourceInfo":{"type":"DEPOSIT","value":"' . $this->account . '"},"destinationInfo":{"type":"IBAN","value":"' . $iban . '","name":"' . $ownerName . '"},"amount":' . $amount . '},"context":{"data":[{"key":"CurrentUserType","value":"OWNER"},{"key":"CurrentUserId","value":"' . @$this->data['CurrentUserId'] . '"},{"key":"language","value":"fa"}]}}';
            $url2 = 'https://ib.postbank.ir/netway/api/Otp/generateOtp';
            $res2 = $this->call($url2, $ref, $params2);
            if (empty($res2) && $this->http->status_code == '200') {
                $data = [
                    'bankName' => $bankName,
                    'ownerName' => $ownerName,
                    'amount' => $amount,
                    'iban' => $iban,
                ];
                return [
                    'status' => 1,
                    'data' => [
                        'params' => $data,
                    ],
                ];
            } else {
                $this->newLog('otp request failed : ' . var_export($res2, true) . ' http status code:' . $this->http->status_code, 'payaTransfer');
                $error = 'otp request failed';
            }
        } else {
            // TODO : we can use this error to disable withdraw
            $error = 'invalid_iban';
        }
        return [
            'status' => 0,
            'error' => $error,
        ];
    }

    public function payaTransferStep2($data, $otp)
    {
        $url = 'https://ib.postbank.ir/netway/api/transferMoney/smartTransfer';
        $ref = 'https://ib.postbank.ir/netway/home';
        $params = '{"data":{"amount":' . $data['amount'] . ',"source":"' . $this->account . '","destination":"' . $data['iban'] . '","payId":null,"sourceDescription":"","reason":"DRPA","transferType":"DEPOSIT_TO_ACH","receiver":"' . $data['ownerName'] . '"},"context":{"data":[{"key":"CurrentUserType","value":"OWNER"},{"key":"CurrentUserId","value":"' . @$this->data['CurrentUserId'] . '"},{"key":"language","value":"fa"},{"key":"ticket","value":"' . $otp . '"}]}}';
        $res = $this->call($url, $ref, $params);
        if ((!isset($res['success']) || $res['success'] == true) && isset($res['trakingNumber'])) {
            return [
                'status' => 1,
                'peygiri' => $res['trakingNumber'],
                'dest' => $res['destinationDepositOwnerName'],
            ];
        } else {
            $error = @$res['exceptionsData'][0]['exceptionName'];
        }
        return [
            'status' => 0,
            'error' => '',
        ];
    }

    public function getTransferRemainingLimit()
    {
        $url = 'https://ib.postbank.ir/netway/api/transferMoney/transfersConstraintInfo?data%5B0%5D.key=CurrentUserType&data%5B0%5D.value=OWNER&data%5B1%5D.key=CurrentUserId&data%5B1%5D.value=' . $this->data['CurrentUserId'] . '&data%5B2%5D.key=language&data%5B2%5D.value=fa';
        $ref = 'https://ib.postbank.ir/netway/home';
        $res = $this->call($url, $ref, '', 'get');
        $this->newLog(var_export($res, true), 'getTransferRemainingLimit');
        if (isset($res['transfersConstraintInfo']['achNormalTransfer'])) {
            $todayPaya = $res['transfersConstraintInfo']['achNormalTransfer']['maxPriceDailyToOtherDeposit'];
            $monthPaya = $res['transfersConstraintInfo']['achNormalTransfer']['maxPriceMonthlyToOtherDeposit'];
            $todayAcc = $res['transfersConstraintInfo']['normalTransfer']['maxPriceDailyToOtherDeposit'];
            $monthAcc = $res['transfersConstraintInfo']['normalTransfer']['maxPriceMonthlyToOtherDeposit'];
            if ($monthPaya < $todayPaya) {
                $todayPaya = $monthPaya;
            }
            if ($monthAcc < $todayAcc) {
                $todayAcc = $monthAcc;
            }
            return [
                'paya' => $todayPaya,
                'acc' => $todayAcc,
            ];
        } else {
            return false;
        }
    }


    public function getTransferRemainingLimit2()
    {
        $url = $this->fixURL('https://www.rb24.ir/pages/achFundTransfer');
        $pg = $this->get($url, 'get');
        $re = '/<span id="achFundTransferForm:maxTransferableAmountOfToday" class="rtlField text">([^<]*)<\/span>/m';
        preg_match_all($re, $pg, $matches, PREG_SET_ORDER, 0);
        if (isset($matches[0][1])) {
            $result['acc'] = $result['paya'] = preg_replace("/[^0-9]/", "", $matches[0][1]);
            return $result;
        }
        return false;
    }

}