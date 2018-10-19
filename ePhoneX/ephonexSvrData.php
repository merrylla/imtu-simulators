<?php

function errRsp($code, $msg)
{

$rsp="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<Errors>
<Error code=\"$code\">
$msg
</Error>
</Errors>";

return $rsp;
}

function topUpRsp($rA)
{

$PhoneUS=$rA['PhoneUS'];
$AgentDutyCode=$rA['AgentDutyCode'];
$CodeContext=$rA['CodeContext'];
$amount=$rA['amount'];
$phoneNumber=$rA['phoneNumber'];
$ID=$rA['ID'];
$Code=$rA['Code'];
$tid=(string) rand();

$storeFee="";
$rechargePercent="";
$totalValue="";
$rechargeAmount="";

$storeFee="";
$rechargePercent="";
$totalValue="";
$rechargeAmount="";

if (isset($rA['storeFee']) ) $storeFee="storeFee=\"".$rA['storeFee']."\"";
if (isset($rA['rechargePercent']) ) $rechargePercent="rechargePercent=\"".$rA['rechargePercent']."\"";
if (isset($rA['totalValue']) ) $totalValue="totalValue=\"".$rA['totalValue']."\"";
if (isset($rA['rechargeAmount']) ) $rechargeAmount="rechargeAmount=\"".$rA['rechargeAmount']."\"";

$rsp="<?xml version=\"1.0\" encoding=\"UTF-8\"?><custRechargeRS><Success/>
<POS>
  <Source AgentDutyCode=\"$AgentDutyCode\">
  <RequestorID ID=\"$ID\">
  <CompanyName Code=\" $Code\" CodeContext=\" $CodeContext\" />
  </RequestorID>
  </Source>
</POS>
<Customer>
  <Name>John</Name>
  <LastName>Doe</LastName>
  <Email> jdoe@ephonex.com </Email>
  <PhoneUS>$PhoneUS</PhoneUS>
</Customer>
<Transaction transactionID=\"$tid\" $storeFee $rechargePercent $totalValue $rechargeAmount>
  <cbPhone amount=\"$amount\">
  <Name>Jane</Name>
  <LastName>Doe</LastName>
  <Phone>$phoneNumber</Phone>
  </cbPhone>
</Transaction>
</custRechargeRS>";

//<transaction transactionID=\"$tid\" storeFee=\"20.00\" rechargePercent=\"30\" totalValue=\"111.00\" rechargeAmount=\"200.00\">

return $rsp;

} //topUpRsp

Class TopupObj extends RmttBase {

    public $x_account_number;
//x_phoneNumber

    public $x_Amount;
//x_amount

    public $x_UserName;
//x_Code
    public $x_Password;
//x_CodeContext

    public $x_x1;
//x_AgentDutyCode
    public $x_x2;
//x_ID
    public $x_x3;
//x_PhoneUS

    public $r_ReturnCode;
//r_Status
    public $r_ReturnMessage;
//r_Message

    public $r1;
//r_storeFee
    public $r2;
//r_rechargePercent
    public $r3;
//r_totalValue
    public $r4;
//r_rechargeAmount

    public $x_action;
//abort
    public $delay;

    function remap() {

       $map=array( 'x_account_number' => 'x_phoneNumber',
                     'x_Amount'   => 'x_amount', 
                     'x_UserName'   => 'x_Code', 
                     'x_Password'  => 'x_CodeContext',
                       'x_x1' => 'x_AgentDutyCode',
                       'x_x2' => 'x_ID',
                       'x_x3' => 'x_PhoneUS',

                       'r1' => 'r_storeFee',
                       'r2' => 'r_rechargePercent',
                       'r3' => 'r_totalValue',
                       'r4' => 'r_rechargeAmount',

                       'x_action' => 'abort',

                   'r_ReturnCode' => 'r_Status',
                'r_ReturnMessage' => 'r_Message'

              );


       foreach ($map as $old => $new) {

           if (isset($this->$old)) {
                $this->$new = $this->$old;

           }
           else
                $this->$new = NULL;

           unset($this->$old);
        }//end foreach
    }//end function remap

   function setAfterDb($p) {

//errlog("INFO: dummping DB: ", $this);

            if (!isset($this->delay)) $this->delay='0'; 

            if (!isset($this->r_Status)) $this->r_Status= '0';
            if (!isset($this->r_Message)) $this->r_Message= 'ok';

            if ($this->abort != 'true') $this->abort = 'false';

            if ($this->r_Status != '0') throw new Exception($this->r_Message, $this->r_Status);

            $this->r_phoneNumber = $p['phoneNumber'];
            $this->r_amount = $p['amount'];
            $this->r_Code = $p['Code'];
            $this->r_CodeContext = $p['CodeContext'];
            $this->r_AgentDutyCode = $p['AgentDutyCode'];
            $this->r_ID = $p['ID'];
            $this->r_PhoneUS = $p['PhoneUS'];

            $rA=processRequest($p, $this);

//errlog("Info: processRequest data: ", $rA);

            return topUpRsp($rA);

   }//setAfterDb

} //TopupObj
?>
