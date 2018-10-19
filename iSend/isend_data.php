<?php

Class ISpostSimplePayment extends RmttBase {

    public $x_account_number;
//Account
    public $x_Amount;
    public $r_ReferenceID;
//r_ExternalTransactionID
    public $x_UserName;
    public $x_Password;
//x_AgentID
//x_AgentPassword
    public $x_DealerTransactionID;
//x_Teller
    public $x_EXTCODE;
//x_ValidateOnly
    public $x_sourceMSISDN;
//x_BillerID
    public $x_SELECTOR;
//x_PaymentServiceTypeID
    public $x_DateFrom;
//x_EntryTimeStamp
    public $x_PersonalMessage;
//x_InfoParameters type="tns:ArrayOfInfoParameter"

    public $r_TransactionID;
//r_TransactionID
    public $r_BusinessAccount;
//r_TotalAgentFee
    public $r_FeeAccount;
//r_TotalCustomerFee
    public $r_ReturnMessage;
//r_ReceiptText
    public $r_ReturnCode;
//r_Status (good codes are: New, Pending, Processed, Failed, Locked, Cancelled)

    public $r_TransactionAmount;
//TotalPaymentAmount
    public $r_ExchangeRate;
//r_ExchangeRate
    public $x_DateTo;
//r_EntryTimeStamp
    public $r_Result;
//r_ErrorCodes
    public $r_symbol;
//ConfirmationRequired
    public $r_LocalCurrency;
//CurrencyCode

    public $delay;
    public $state;

    function remap() {

       $map=array( 'x_account_number' => 'x_Account',
                  'r_ReferenceID'    => 'r_ExternalTransactionID',
                  'x_UserName'    => 'x_AgentID',
                  'x_Password'    => 'x_AgentPassword',
                  'x_DealerTransactionID'    => 'x_Teller',
                  'x_EXTCODE'   => 'x_ValidateOnly',
                  'x_sourceMSISDN'    => 'x_BillerID',
                  'x_SELECTOR'    => 'x_PaymentServiceTypeID',
                  'x_DateFrom'    => 'x_EntryTimeStamp',
                  'x_PersonalMessage'    => 'x_InfoParameters',
                  'r_BusinessAccount'    => 'r_TotalAgentFee',
                  'r_FeeAccount'    => 'r_TotalCustomerFee',
                  'r_ReturnMessage'    => 'r_ReceiptText',
                  'r_ReturnCode'    => 'r_Status',
                  'r_TransactionAmount'    => 'TotalPaymentAmount',
                  'x_DateTo'    => 'r_EntryTimeStamp',
                  'r_Result'    => 'r_ErrorCodes',
                  'r_symbol'    => 'ConfirmationRequired',
                  'r_LocalCurrency'    => 'CurrencyCode');
      
       foreach ($map as $old => $new) {

           if (isset($this->$old)) {
                  $this->$new = $this->$old;
                  unset($this->$old);
            }
        }//end foreach
    }//end function remap

} //end of ISpostSimplePayment class

?>
