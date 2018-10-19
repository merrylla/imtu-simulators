<?php

Class OrgInfoData extends RmttBase {

    public $r_status_code;
    public $r_error_code;
    public $r_error_txt;
    public $r_msisdn;
    public $r_destination_msisdn;

    public $r_originating_currency;
    public $r_destination_currency;
    public $r_authentication_key;

    public $r_input_value;
    public $r_output_value;
    public $r_topup_sent_amount;
    public $r_debit_amount_validated;
   
    public $r_date;
    public $r_transaction_status;
    public $r_transaction_error_code;
    public $r_transaction_error_txt;
    public $r_pin_based;
    public $r_pin_validity;
    public $r_pin_code;
    public $r_pin_ivr;
    public $r_pin_serial;
    public $r_pin_value;
    public $r_pin_option_1;
    public $r_pin_option_2;
    public $r_pin_option_3;

   

    function OrgInfoData() {
           $this->r_status_code= 0;
           $this->r_error_code= 0;
           $this->r_error_txt= 'Tranaction Success';
    }

    function cpFrTp($p) {

//dump_errorlog("DEBUG dumpping dbObj: ", $p);
           $this->r_transaction_status = $p->r_status_code;
           $this->r_transaction_error_code = $p->r_error_code;
           $this->r_transaction_error_txt = $p->r_error_txt;
           $this->r_msisdn=$p->r_msisdn;
           $this->r_destination_msisdn=$p->r_destination_msisdn;
           $this->r_originating_currency=$p->r_originating_currency;
           $this->r_destination_currency=$p->r_destination_currency;
           $this->r_authentication_key=$p->r_authentication_key;
           $this->r_input_value=$p->r_input_value;
           $this->r_output_value=$p->r_output_value;
           $this->r_topup_sent_amount=$p->r_topup_sent_amount;
           $this->r_debit_amount_validated=$p->r_validated_input_value;
           $this->r_date= dateformat();
    }

    function setORGstatus($code, $msg) {
      $this->r_error_code=$code;
      $this->r_error_txt= $msg;  
    }
}

Class OrgTopUpData extends RmttBase {

    public $x_account_number;
//x_destination_msisdn
    public $x_UserName;
//x_login
    public $x_Password;
//Password
    public $x_Checksum;
//x_md5
    public $r_ReferenceID;
//r_key
    public $x_Amount;
//x_product
    public $x_sourceMSISDN;
//x_msisdn
    public $x_DealerTransactionID;
//x_operatorid
    public $x_Action;
//x_send_sms
    public $x_PersonalMessage;
//x_sms
    public $x_EXTCODE;
//x_cid1
    public $x_EXTCODE2;
//x_cid2
    public $x_LANGUAGE1;
//x_cid3
    public $x_CorporateID;
//x_reserved_id

    public $r_ReturnCode;
//r_error_code
    public $r_Result;
//r_status_code
    public $r_ReturnMessage;
//r_error_txt

    public $r_msisdn;
    public $r_destination_msisdn;

    public $r_CurrencyType;
//r_originating_currency
    public $r_LocalCurrency;
//r_destination_currency
    public $r_amountSource;
//r_input_value
    public $r_FeeAccount;
//r_validated_input_value
    public $r_amountTarget;
//r_output_value
    public $r_TransactionAmount;
    public $r_topup_sent_amount;
    public $r_authentication_key;
    public $r_sms_sent;
    public $r_TransactionID;
//r_transactionid
    public $r_cid1;
    public $r_cid2;
    public $r_cid3;
    public $r_MNO;
//r_operator
    public $r_RecipientPhoneNumber;
//r_country
    public $delay;
    public $state;

    function remap() {

       $map=array( 'x_account_number' => 'x_destination_msisdn',
                   'x_UserName' => 'x_login',
                   'x_Password' => 'Password',
                   'x_Checksum' => 'x_md5',
                   'r_ReferenceID' => 'x_key',
                   'x_Amount' => 'x_product',
                   'x_sourceMSISDN' => 'x_msisdn',
                   'x_DealerTransactionID' => 'x_operatorid',
                   'x_Action' => 'x_send_sms',
                   'x_PersonalMessage' => 'x_sms',
                   'x_EXTCODE' => 'x_cid1',
                   'x_EXTCODE2' => 'x_cid2',
                   'x_LANGUAGE1' => 'x_cid3',
                   'x_CorporateID' => 'x_reserved_id',
                   'r_ReturnCode' => 'r_error_code',
                   'r_Result' => 'r_status_code',
                   'r_ReturnMessage' => 'r_error_txt',
                   'r_CurrencyType' => 'r_originating_currency',
                   'r_LocalCurrency' => 'r_destination_currency',
                   'r_amountSource' => 'r_input_value',
                   'r_FeeAccount' => 'r_validated_input_value',
                   'r_amountTarget' => 'r_output_value',
                   'r_TransactionAmount' => 'topup_sent_amount',
                   'r_TransactionID' => 'r_transactionid',
                   'r_MNO' => 'r_operator',
                   'r_RecipientPhoneNumber' => 'r_country' 
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

} //end of OrgTopUpData class

?>
