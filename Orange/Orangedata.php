<?php

Class TransactionContext {

    public $ReceivableId;
    public $Amount;
    public $IatTrxId;   //Tid (burnReq)
    public $SNwId;   
    public $SNwTrxId;   // DealerTransactionID (burnReq)
    public $ReceivableValueSc;
    public $msisdn;     // burnReq
    public $SCountryCode;
    public $DurationType;
    public $ReceivableEndValidityDate;
    public $Status;
    public $ReturnMessage;
    public $burnReqAction;
    public $burnReqDelay;
    public $burnReqRetry;
    public $statusReqAction;
    public $statusReqDelay;
    public $statusReqRcode;
    public $statusReqRetry;
}

Class TopupObj extends RmttBase {

    public $x_account_number;
//x_MSISDN2

    public $x_Amount;
//x_ReceivableValueSc

    public $x_UserName;
//x_SNwId

    public $x_x1;
//x_SCountryCode
    public $x_x2;
//x_RCountryCode
    public $x_x3;
//x_SendingBearer  (eg: USSD,STK,SMS,IVR,WEB, ...etc)
    public $x_x4;
//x_RechargeType (1=R2P, 2=P2P)


    public $r_ReturnCode;
//r_Status
    public $r_ReturnMessage;
//r_Message

    public $r_IatTrxId;   //Tid
    public $r_SNwTrxId;   // DealerTransactionID;

    public $r_IatTimeStamp;
    public $r_ReceivableId;

    public $r1;
    public $r2;
//  burnReqAction
//  burnReqDelay

    public $r3;
    public $r4;
    public $r5;
//  statusReqAction
//  statusReqDelay
//  statusReqRcode

    public $r6;
    public $r7;
//burnReqRetry
//statusReqRetry

    public $abort;
    public $delay;

    function remap() {

       $map=array( 'x_account_number' => 'x_MSISDN2',
                     'x_UserName'   => 'x_SNwId', 
                     'x_Amount'   => 'x_ReceivableValueSc', 
                       'x_x1' => 'x_SCountryCode',
                       'x_x2' => 'x_RCountryCode',
                       'x_x3' => 'x_SendingBearer',
                       'x_x4' => 'x_RechargeType',

                       'r1' => 'burnReqAction',
                       'r2' => 'burnReqDelay',
                       'r3' => 'statusReqAction',
                       'r4' => 'statusReqDelay',
                       'r5' => 'statusReqRcode',
                       'r6' => 'burnReqRetry',
                       'r7' => 'statusReqRetry',

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

            if (!isset($this->burnReqDelay)) $this->burnReqDelay='0'; 
            if (!isset($this->burnReqRetry)) $this->burnReqRetry= '0';
            if (!isset($this->statusReqDelay)) $this->statusReqDelay='0'; 

            if (!isset($this->statusReqAction)) $this->statusReqAction = 'Transaction approved';
            if (!isset($this->statusReqDelay)) $this->statusReqDelay= '0';
            if (!isset($this->statusReqRcode)) $this->statusReqRcode= '0';
            if (!isset($this->statusReqRetry)) $this->statusReqRetry= '0';

            $this->delay = $this->burnReqDelay;

            if (!isset($p->SNwId)) throw new Exception("ERROR - Missing SNwId ", 31012); 
            if (!isset($p->ReceivableValueSc)) throw new Exception("ERROR - Missing x_ReceivableValueSc", 31001); 
//            if (!isset($p->SNwTrxId)) throw new Exception("ERROR - Missing SNwTrxId", 002); 
            if (!isset($p->SendingBearer)) throw new Exception("ERROR - Missing SendingBearer", 002); 
            if (!isset($this->r_Status)) 
                       $this->r_Status = '0';
            if (!isset($this->r_Message))
                       $this->r_Message = 'Operation Successfull';

            if (preg_match('/ABORT/i',$this->burnReqAction)) {
                 $this->abort = true;
            }
            else 
                 $this->abort = false;

            $this->r_IatTimeStamp = date("Y-m-d\TH:i:sO"); 
            $this->r_SNwTrxId= $p->SNwTrxId;
            $this->r_ReceivableId= $p->ReceivableId  ;

   }//setAfterDb

} //TopupObj

?>
