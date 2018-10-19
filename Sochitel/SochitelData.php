<?php

Class TopupObj extends RmttBase {

    public $x_account_number;
//x_mobileno

    public $x_Amount;
//x_amount
    public $x_UserName;
//x_username
    public $x_Password;
//x_password
    public $x_Pin;
    public $r_result;
//status
    public $r_ReturnCode;
//errorCode
    public $r_ReturnMessage;
//r_errorMessage

    public $r_TransactionID;
    public $r_TransactionAmount;
//r_amount
    public $r_amountSource;
//r_balBefore
    public $r_amountTarget;
//r_balAfter

    public $abort;
    public $delay;

    function remap() {

       $map=array( 'x_account_number' => 'x_mobileno',
                  'x_UserName'    => 'x_username',
                  'x_Password'    => 'x_password',
                     'x_Amount'   => 'x_amount', 
                     'x_Pin'      => 'x_pin', 

                       'r_result' => 'r_status',
                   'r_ReturnCode' => 'errorCode',
                'r_ReturnMessage' => 'r_errorMessage',
//           'r_TransactionID'    => 'r_TransactionID',
           'r_TransactionAmount'  => 'r_amount',
                'r_amountSource'  => 'r_balBefore',
                'r_amountTarget'  => 'r_balAfter'
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

            if (!isset($p['delay'])) $p['delay']=0; 
            if (!isset($p['username'])) throw new Exception("ERROR - Missing username", 003); 
            if (!isset($p['password'])) throw new Exception("ERROR - Missing password", 003); 
            if (!isset($p['amount'])) throw new Exception("ERROR - Missing amount", 002); 
            if (!isset($p['mobileno'])) throw new Exception("ERROR - Missing mobileno", 002); 
            if (!isset($p['agentTransactionId'])) throw new Exception("ERROR - Missing agentTransactionId", 002); 
            if (!isset($this->r_status)) 
                       $this->r_status = '0';
            if (!isset($this->errorCode)) 
                       $this->errorCode= '100'; 
            if (!isset($this->r_errorMessage))
                       $this->r_errorMessage = 'OK';


            $errorCode=$this->errorCode;

            $matched=preg_match('/[0-9]{1,4}/',$errorCode, $ecode);
            if ($matched)
                 $this->errorCode=$ecode[0];
            else
                 $this->errorCode='100';


            if (preg_match('/ABORT/i',$errorCode)) {
                 $this->abort = true;
            }
            else 
                 $this->abort = false;

            $resp=$this->r_status.":".$this->errorCode.":".$this->r_errorMessage;

            //if ($this->errorCode =='PSPD202') return '0'; 
            if ($this->errorCode !='100') return $resp; 

            if (!isset($this->r_amount))
                       $this->r_amount= $p['amount'];
            if (!isset($this->r_balBefore))
                       $this->r_balBefore= $p['amount']+1000;
            if (!isset($this->r_balAfter))
                       $this->r_balAfter= $p['amount'];

            $tid = rand(); 

            $options="$tid".":".$this->r_amount.":".$this->r_balBefore.":".$this->r_balAfter;

            return  $resp.":".$options;

   }//setAfterDb

} //TopupObj

?>
