<?php

Class ComVTopUpData extends RmttBase {

    public $x_account_number;
//x_MSISDN2
    public $x_UserName;
//x_LOGINID
    public $x_Password;
//x_PASSWORD
    public $x_Amount;
//x_AMOUNT
    public $x_sourceMSISDN;
//x_MSISDN
    public $x_EXTCODE;
    public $x_EXTCODE2;
//x_EXTNWCODE
    public $x_PIN;
    public $x_LANGUAGE1;
    public $x_LANGUAGE2;
    public $x_utcDateTime;
//x_DATE
    public $r_ReferenceID;
//r_EXTREFNUM
    public $r_ReturnCode;
//r_TXNSTATUS
    public $r_ReturnMessage;
//r_MESSAGE
    public $x_DateFrom;
//r_DATE
    public $r_TransactionID;
//r_TXNID
    public $delay;
    public $state;

    function remap() {

       $map=array( 'x_account_number' => 'x_MSISDN2',
                  'x_Amount'         => 'x_AMOUNT',
                  'x_UserName'       => 'x_LOGINID',
                  'x_Password'       => 'x_PASSWORD',
                  'x_sourceMSISDN'   => 'x_MSISDN',
                  'x_EXTCODE2'       => 'x_EXTNWCODE',
                  'x_utcDateTime'    => 'x_DATE',
                  'r_ReferenceID'    => 'r_EXTREFNUM',
                  'r_ReturnCode'     => 'r_TXNSTATUS',
                  'r_ReturnMessage'  => 'r_MESSAGE',
                  'x_DateFrom'       => 'r_DATE',
                  'r_TransactionID'  => 'r_TXNID');
      
       foreach ($map as $old => $new) {

           if (isset($this->$old)) {
                  $this->$new = $this->$old;
                  unset($this->$old);
            }
        }//end foreach
    }//end function remap

} //end of PaqTopUpData class

Class ComVBalData extends RmttBase {

    public $x_account_number;
//x_agentId
    public $r_CurrencyType;
//currencyId
    public $r_LocalCurrency;
//name
    public $r_symbol;
    public $x_DateTo;
//dueDate
    public $r_ReturnCode;
    public $r_ReturnMessage;
//returnString
    public $balance;
//r_credit
    public $delay;


    function remap() {

       $map=array( 'x_account_number' => 'x_agentId',
                  'r_CurrencyType'    => 'r_currencyId',
                  'r_LocalCurrency'   => 'r_name',
                  'x_DateTo'          => 'r_dueDate',
                  'r_ReturnMessage'   => 'r_returnString',
                  'balance'           => 'r_credit');
      
       foreach ($map as $old => $new) {

           if (isset($this->$old)) {
                  $this->$new = $this->$old;
                  unset($this->$old);
            }
       }//end foreach
    } //remap
} //end of PaqBalData class

Class ComVStatusCmd extends RmttBase {

    public $x_Product;
    public $x_utcDateTime;
    public $x_Checksum;
    public $r_ReferenceID;
    public $r_ReturnCode;
    public $r_ReturnMessage;
    public $r_TransactionID;
    public $r_CurrencyType;
    public $r_TransactionAmount;
    public $r_RecipientPhoneNumber;
    public $r_TransactionDataTime;

} //end of PaqStatusCmd class


?>
