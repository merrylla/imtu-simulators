<?php

function PurchasePinResponse($rA)
{
 errlog("INFO: dummping rA: ", $rA);

    $tid = rand();
    $pinNumber = "76746".$rA['invoiceNumber'];
#$pinNumber = "A7B7C6".$rA['invoiceNumber'];
    $controlNumber = "76285".$rA['invoiceNumber'];

    $pin = array('pinNumber' => $pinNumber,
              'controlNumber' => $controlNumber);

     $pinA = array('pin' => $pin);

    $skuId= $rA['skuId'];
    $name = $skuId."ppnCard1";
    $faceValue = $rA['faceValueAmount'];
    $instructions = 'CallCSR';


    $card = array('pins' => $pinA,
                 'skuId' => $skuId,
                  'name' => $name,
             'faceValue' => $faceValue,
          'instructions' => $instructions );

    $cardA= array('card' => $card);

     $invoiceNumber= (int) $rA['invoiceNumber'];
     $transactionDateTime=date("Y-m-d\TH:i:s");
     $invoiceAmount=$rA['invoiceAmount'];
     $faceValueAmount=$rA['faceValueAmount'];

     $invoice = array ( 'cards' => $cardA,
                'invoiceNumber' => $invoiceNumber,
          'transactionDateTime' => $transactionDateTime,
                'invoiceAmount' => $invoiceAmount,
              'faceValueAmount' => $faceValueAmount);


      $version=$rA['version'];
      $responseCode=$rA['responseCode'];
      $responseMessage=$rA['responseMessage'];

      $orderResponse = array('invoice' => $invoice,
                            'version' => $version,
                            'responseCode' => $responseCode,
                            'responseMessage' => $responseMessage);

    $rsp2 = array('orderResponse' => $orderResponse);

    return $rsp2;

}//PurchasePinResponse


function PurchaseRtr2Response($rA)
{

    $pinA = array('pin' => $pin);
    $faceValueAmount=$rA['localCurrencyAmount'];

    $skuId= $rA['skuId'];
    $name = "Telcel $".$faceValueAmount;
    $faceValue = $faceValueAmount;

    $card = array( 'skuId' => $skuId,
                  'name' => $name,
             'faceValue' => $faceValue );

    $cardA= array('card' => $card);

     $invoiceNumber= rand(1000000,9999999);
     $transactionDateTime=date("Y-m-d\TH:i:s");
     $invoiceAmount=$rA['invoiceAmount'];

     $invoice = array ( 'cards' => $cardA,
                'invoiceNumber' => $invoiceNumber,
          'transactionDateTime' => $transactionDateTime,
                'invoiceAmount' => $invoiceAmount,
              'faceValueAmount' => $faceValueAmount);
 
    $topup= array ('localCurrencyAmount' => $rA['localCurrencyAmount'],
                   'salesTaxAmount'      => $rA['salesTaxAmount'],
       'localCurrencyAmountExcludingTax' => $rA['localCurrencyAmountExcludingTax'],
                   'localCurrencyName'   => $rA['localCurrencyName'] );
                   //'newAccountBalance'   => $rA['newAccountBalance'] ); 

    $orderResponse = array('invoice' => $invoice,
                            'topUp' => $topup,
                            'version' => $rA['version'],
                            'responseCode' => $rA['responseCode'],
                            'responseMessage' => $rA['responseMessage'] );

    $rsp2 = array('orderResponse' => $orderResponse);
    return $rsp2;
} //PurchaseRtr2Response

Class TopupObj extends RmttBase {

    public $type;
//Invoice or TopUp

    public $x_account_number;
//x_mobile (topup)
    public $x_UserName;
//x_skuId
    public $r_skuId;

    public $x_Amount;
//x_quantity (ppin)
//x_amount (topup)

    public $r_referenceid;
//corelationId

    public $x1;
//x_senderMobile (rtr)

    public $x2;
//r_version;

    public $r1;
    public $r2;
    public $r3;
    public $r4;
    public $r5;
//r_invoice
//r_invoiceNumber
//r_transactionDateTime
//r_invoiceAmount
//r_faceValueAmount

//r_topUp
//r_localCurrencyAmount
//r_salesTaxAmount
//r_localCurrencyAmountExcludingTax
//r_localCurrencyName
//r_newAccountBalance

    public $r_ReturnCode;
//r_responseCode
    public $r_ReturnMessage;
//r_responseMessage

    public $x_action;
//abort
    public $delay;

    function remap($type) {

       $this->type = $type;

       if ($type == 'invoice' ) {

          $map=array(  'x_account_number' => 'accountDummy',
                       'x_Amount'   => 'x_quantity', 
                       'r1'   => 'r_invoiceNumber', 
                       'r2'   => 'r_transactionDateTime', 
                       'r3'   => 'r_invoiceAmount', 
                       'r4'   => 'r_faceValueAmount' 
                    );
       }
       else { // $type='topup'

          $map=array( 'x_account_number' => 'x_mobile',
                       'x_Amount'   => 'x_amount', 
                       'x1'   => 'x_senderMobile', 
                       'r1'   => 'r_localCurrencyAmount', 
                       'r2'   => 'r_salesTaxAmount', 
                       'r3'   => 'r_localCurrencyAmountExcludingTax', 
                       'r4'   => 'r_localCurrencyName', 
                       'r5'   => 'r_newAccountBalance' 
                     );
       }

       $map0=array( 'x2' => 'r_version',
                     'r_referenceid'   => 'corelationId', 
                     'x_UserName' => 'x_skuId', 
                       'x_action' => 'abort',
                   'r_ReturnCode' => 'r_responseCode',
                'r_ReturnMessage' => 'r_responseMessage'

              );

       $map=array_merge($map, $map0);

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

            if (!isset($this->r_responseCode)) $this->r_responseCode= '000';
            if (!isset($this->r_responseMessage) || $this->r_responseCode == '000') $this->r_responseMessage= '';
            if (!isset($this->r_version)) $this->r_version= $p->version;

            $this->r_skuId= $p->skuId;

            if ($this->type == 'invoice' ) {

                $amount=2400;
                $invoice=rand(100000, 500000);
                if (!isset($this->r_invoiceNumber)) $this->r_invoiceNumber= $invoice;
                if (!isset($this->r_invoiceAmount)) $this->r_invoiceAmount= $amount;
                if (!isset($this->r_faceValueAmount)) $this->r_faceValueAmount= $amount;
                if (!isset($this->x_quantity)) $this->x_quantity = 1;

             }
             else { // for topup


                if (!isset($this->r_localCurrencyAmount)) $this->r_localCurrencyAmount= $p->amount;
                if (!isset($this->r_salesTaxAmount)) $this->r_salesTaxAmount= $p->amount * .1;
                if (!isset($this->r_localCurrencyAmountExcludingTax))
                    $this->r_localCurrencyAmountExcludingTax= $p->amount - $this->r_salesTaxAmount;
                if (!isset($this->r_localCurrencyName)) $this->r_localCurrencyName= 'MXN';
                if (!isset($this->r_newAccountBalance)) $this->r_newAccountBalance= '99003';
             } 

            if ($this->abort != 'true') $this->abort = 'false';

//errlog("INFO: dummping DB: ", $this);

            $rA=processRequest($p, $this);

            if ($this->type == 'invoice' ) return PurchasePinResponse($rA);
            else
                 return PurchaseRtr2Response($rA);

   }//setAfterDb

} //TopupObj
?>
