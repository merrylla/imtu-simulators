<?php

Class IMTUGlobeTopUpCmd extends RmttBase {

    public $x_account_number;
//x_target
    public $x_Amount;
//x_amount
    public $r_ReturnMessage; 
//r_sessionResultCode
    public $r_ReturnCode;
//r_topupResultCode

    public $delay;
    public $state;

    function remap() {

       $map=array( 'x_account_number' => 'x_target',
                  'x_Amount'          => 'x_amount',
                  'r_ReturnMessage'   => 'r_sessionResultCode',
                  'r_ReturnCode'      => 'r_topupResultCode'
                  );
      
       foreach ($map as $old => $new) {

           if (isset($this->$old)) {
                  $this->$new = $this->$old;
                  unset($this->$old);
            }
        }//end foreach
    }//end function remap

} //end of IMTUGlobeTopUpCmd class

?>
