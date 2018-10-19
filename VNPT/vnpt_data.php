<?php

Class TopupObj extends RmttBase {

    public $x_account_number;
//x_targetAccount
    public $x_Amount;
//x_amount
    public $x_UserName;
//x_username
    public $x_Password;
//Password

//token (user returning session id)

//requestID - querry parameter 

//r_epayTransID (tid)

    public $r_ReturnCode;
//r_errorCode
    public $r_ReturnMessage;
//r_errorMessage
    public $r1;
//r_merchantBalance
    public $r2;
//r_merchantID

    public $x_action;
//abort
    public $delay;

    function remap() {

       $map=array( 'x_account_number' => 'x_targetAccount',
                     'x_Amount'   => 'x_amount', 
                     'x_UserName'   => 'x_username', 
                     'x_Password'   => 'Password', 
                   'r_ReturnCode' => 'r_errorCode',
                'r_ReturnMessage' => 'r_errorMessage',
                       'r1' => 'r_merchantBalance',
                       'r2' => 'r_merchantID',

                       'x_action' => 'abort'
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
            if ($this->abort != 'true') $this->abort = 'false';

            if (!isset($this->r_errorCode)) $this->r_errorCode= 0;
            if (!isset($this->r_errorMessage)) $this->r_errorMessage= 'successful';

            if ($this->r_errorCode == 0) $this->r_epayTransID = rand();
            else
                 $this->r_epayTransID='';

            if (!isset($this->r_merchantBalance)) $this->r_merchantBalance = 10000;
            if (!isset($this->r_merchantID )) $this->r_merchantID= $p['username'];


            $rA=processRequest($p, $this);

//errlog("Info: processRequest data: ", $rA);

            return $rA;

   }//setAfterDb

} //TopupObj

function partnerDirectTopupResponse($rA)
{
     return array('partnerDirectTopupResponse' => $rA);
}//partnerDirectTopupResponse

?>
