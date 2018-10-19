<?php

Class PaqTopUpData extends RmttBase {

    public $x_account_number;
//x_destinationMSISDN
    public $x_Amount;
//x_amount
    public $x_sourceMSISDN;
    public $x_PIN;
    public $r_ReferenceID;
//x_externalTransactionId
    public $r_amountSource;
    public $r_amountTarget;
// r_ReturnCode is not in the wsdl, it'll be subpressed in the normal return msg
    public $r_ReturnCode;
    public $r_ReturnMessage;
//returnString
    public $r_sourceCommissionCredit;
    public $r_sourceCommissionDebit;
    public $r_targetCommissionCredit;
    public $r_targetCommissionDebit;
    public $r_TransactionID;
//transactionId
    public $delay;
    public $state;

    function remap() {

       $map=array( 'x_account_number' => 'x_destinationMSISDN',
                  'x_Amount'         => 'x_amount',
                  'r_ReferenceID'    => 'x_externalTransactionId',
                  'r_ReturnMessage'  => 'r_returnString',
                  'r_TransactionID'  => 'r_transactionId');
      
       foreach ($map as $old => $new) {

           if (isset($this->$old)) {
                  $this->$new = $this->$old;
                  unset($this->$old);
            }
        }//end foreach
    }//end function remap

} //end of PaqTopUpData class

Class PaqBalData extends RmttBase {

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

Class PaqStatusCmd extends RmttBase {

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
