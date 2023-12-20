<?php
load('http');
class sample extends banking
{

    public function __construct($data, $user_id, $banking_id)
    {
        // chizai ke bayad load konim va config ro inja anjam midim
    }

    public function logout()
    {
        // age niaz be logout bashe in method farakhuni mishe
    }

    public function login()
    {
        // inja bayad check konim age login hastim true bargardunim
        // va age nistim login konim
    }

    public function autoSigninStep1()
    {
        // bank hai ke niaz be code e payamaki daran mitunim az in method estefade konim
        // va ta marhaleye qabl az vurude code e payamaki berim jolo
        // yani age niaz be login bashe, system miad in method ro call mikone aval
        // bad method e getCodeFromSMS call mishe va payam hai ke oumade ru panel ro behesh pass mikone
        // ounja code e payamaki ro ba pattern i ke darim estekhraj mikonim va return mikonim
    }

    public function getCodeFromSMS($messages,$type=1)
    {
        // ye list az payam hai ke oumade be khate morede nazar tu variable $messages miad va bayad loop konim
        // $type age 1 bud yani bara login bayad pattern check konim va age 2 bud baraye enteqale paya
        // har ko2m match bud code ro estekhraj konim va return konim
        // age hich ko2m match nabud, false return konim
    }

    public function autoSigninStep2($data,$otp)
    {
        // inja code e payamaki ro vared mikonim va login ro takmil mikonim
        // $data khurujie method e autoSigninStep1 e, va $otp ham khurujie method e getCodeFromSMS
    }

    function logStatements($datetime='null', $amount='null')
    {
        // inja surathesab ro migirim va be surate ye array ba format e morede nazar return mikonim
    }

    public function keepSession()
    {
        // bazi bank ha niaz daran ke ye harkate khasi anjam beshe ta login bemunan
    }

    public function getBalances()
    {
        // mojudi va mojudie block shode ro migirim va return mikonim ya age khata gereftim false bar migardunim
    }

    public function payaTransferStep1($sheba,$name,$amount,$note)
    {
        // inja ham mesle login enteqale paya ro ta qable ersale ramze puya mirim jolo
    }

    public function payaTransferStep2($data,$otp,$sheba,$name,$amount,$note)
    {
        // inja ham enteqale paya ro takmil mikonim
        // $data khurujie method e payaTransferStep1 e
        // $otp ham ramze puyast
    }
}
