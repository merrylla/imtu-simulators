<?php


Class TopupObj extends RmttBase {

    public $x_account_number;
//x_destNo
    public $x_Amount;
//x_deno
    public $x_UserName;
//x_UserID
    public $x_Password;
//x_Password 
    public $r_referenceid;
//IDTtransID
    public $x1;
//x_sourceNo
    public $x2;
//x_destNo

    public $x_product;
//x_prodCode

    public $r_ReturnCode;
//r_Status
    public $r_ReturnMessage;
//r_Message
    public $r1;
//r_wcurrency : Currency code of the TP’s account
    public $r2;
//r_ProductPrice: Amount that is deducted from TP account
    public $r3;
//r_AmountCurrency: urrency code of the denomination of the transaction
    public $r4;
//r_AmountAfterTax;

    public $x_action;
//action
    public $delay;

    function remap() {

       $map=array( 'x_account_number' => 'x_destNo',
                     'x_Amount'   => 'x_deno', 
                     'x_UserName'   => 'x_UserID', 
                   'r_referenceid' => 'IDTtransID',
                   'r_ReturnCode' => 'r_Status',
                'r_ReturnMessage' => 'r_Message',
                       'x1' => 'x_sourceNo',
                       'x2' => 'x_destNo',
                       'x_product' => 'x_prodCode',
                       'r1' => 'r_wcurrency',
                       'r2' => 'r_ProductPrice',
                       'r3' => 'r_AmountCurrency',
                       'r4' => 'r_AmountAfterTax',

                       'x_action' => 'action'
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

errlog("INFO: dummping DB: ", $this);
              
            if (!isset($this->delay)) $this->delay='0'; 

            if (!isset($this->r_Status)) $this->r_Status= '000';

            $statusA=array('000');
            if ($this->action == 'true') $this->abort = 'true';
            else {
                $this->abort = 'false'; 
                $mA=array();
                $cList=':'.$this->action;
                $cnt=preg_match_all('/:(\d+)/', $cList, $mA); 
                if ($cnt>0) $statusA=$mA[1];
            }

            sessStore($p->transID, $statusA); 

            $rA=processRequest($p, $this);

//errlog("Info: processRequest data: ", $rA);

            return $rA;

   }//setAfterDb

} //TopupObj

function errRsp($code)
{
   $rsp="<?xml version=\"1.0\" encoding=\"utf-8\"?><string xmlns=\"http://tempuri.org/\">$code</string>";
}

function Request_ReloadRsp($code)
{
   $tid=(string) rand();

   $rsp="<?xml version=\"1.0\" encoding=\"utf-8\"?><string xmlns=\"http://tempuri.org/\">$code, $tid </string>";

return $rsp;

} //topUpRsp

function Request_ReloadAmountRsp($rA)
{
//errlog("INFO: Request_ReloadAmountRsp input: ", $rA);

     $tid=(string) rand();
     $status='000';
     $wcurrency='USD';
     $ProductPrice='1000';
     $AmountCurrency='XYZ';
     $AmountAfterTax='900'; 

     if (array_key_exists('Status', $rA)) $status=$rA['Status'];
     if (array_key_exists('WalletCurrency', $rA)) $wcurrency=$rA['WalletCurrency'];
     if (array_key_exists('ProductPrice', $rA)) $ProductPrice=$rA['ProductPrice'];
     if (array_key_exists('AmountCurrency', $rA)) $AmountCurrency=$rA['AmountCurrency'];
     if (array_key_exists('AmountAfterTax', $rA)) $AmountAfterTax=$rA['AmountAfterTax'];

     $SerialNo='mno-tid.'.$tid;

     if ($status != '000') { $ProductPrice=''; $AmountCurrency=''; $AmountAfterTax='';}
     if ($status != '001') { $wcurrency='';}

$xmlspec="<?xml version=\"1.0\" encoding=\"utf-8\"?>";

     $rsp="<Request_ReloadAmountResponse xmlns=\"http://tempuri.org/\">
           <Request_ReloadAmountResult>
           <string xmlns=\"http://tempuri.org/\">
           <RefID>$tid</RefID>
           <Status>$status</Status>
           <WalletCurrency>$wcurrency</WalletCurrency>
           <ProductPrice>$ProductPrice</ProductPrice>
           <AmountCurrency>$AmountCurrency</AmountCurrency>
           <AmountAfterTax>$AmountAfterTax</AmountAfterTax>
           <SerialNo>$SerialNo</SerialNo>
           </string>
           </Request_ReloadAmountResult>
           </Request_ReloadAmountResponse>";

return $rsp;

} //Request_ReloadAmountRsp

function Transaction_InquiryResponse($code)
{
/*
   $rsp="<?xml version=\"1.0\" encoding=\"utf-8\"?><string xmlns=\"http://tempuri.org/\">$code </string>";
*/
   $rsp=$code;
   return $rsp;
}

function Product_Price_InquiryResponse($status, $currency, $price)
{
   $rsp="<?xml version=\"1.0\" encoding=\"utf-8\"?>
         <string xmlns=\"http://tempuri.org/\">
         <Status>$status</Status>
         <Currency>$currency</Currency>
         <ProductPrice>$price</ ProductPrice>
         </string>";

    return $rsp;
}

function EWallet_InquiryResponse($status, $currency, $price)
{

   $rsp="<?xml version=\"1.0\" encoding=\"utf-8\"?>
         <string xmlns=\"http://tempuri.org/\">
         <Status>$status</Status>
         <Currency>$currency</Currency>
         <WalletBalance>$bal</ WalletBalance >
         </string>";

   return $rsp;
}

?>
