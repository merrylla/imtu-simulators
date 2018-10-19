<?php

function topUpRsp($code)
{

$rsp=array('Error' => $code);
return $rsp;

} //topUpRsp

Class TopupObj extends RmttBase {

    public $x_account_number;
//x_Telnum

    public $x_Amount;
//x_Valor

    public $x_UserName;
//x_Venide (IDT001)
    public $x_Password;
//x_Venpas (IDT001)

    public $x1;
//x_Paicod (country code)
    public $x2;
//x_Opecod (operator)
    public $x3;
//x_Recfee (fee)
    public $r_referenceid;
//x_Recrem

    public $r_ReturnCode;
//r_Status
    public $r_ReturnMessage;
//r_Message

    public $x_action;
//abort
    public $delay;

    function remap() {

       $map=array( 'x_account_number' => 'x_Telnum',
                     'x_Amount'   => 'x_Valor', 
                     'x_UserName'   => 'x_Venide', 
                     'x_Password'  => 'x_Venpas',
                       'x1' => 'x_Paicod',
                       'x2' => 'x_Opecod',
                       'x3' => 'x_Recfee',
              'r_referenceid' => 'x_Recrem',

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

            if (!isset($this->r_Status)) $this->r_Status= '00';
            $this->r_Message= $this->r_Status;

            if ($this->abort != 'true') $this->abort = 'false';

            if ($this->r_Status != '00') return $this->r_Status;

            $rA=processRequest($p, $this);

//errlog("Info: processRequest data: ", $rA);

            return $this->r_Status;

   }//setAfterDb

} //TopupObj
?>
