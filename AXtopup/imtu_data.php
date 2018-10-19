<?php

Class axTopUp extends RmttBase {

    public $x_Product;
    public $x_Amount;
    public $x_account_number;
    public $x_Action;
    public $x_CurrencyType;
    public $x_utcDateTime;
    public $x_Checksum;
    public $r_ReturnCode;
    public $r_ReferenceID;
    public $r_ReturnMessage;
    public $r_TransactionID;
    public $r_ExchangeRate;
    public $r_LocalCurrencyEquivalent;
    public $r_LocalCurrency;
    public $r_MNO;
    public $delay;
    public $state;

} //end of axTopUp class

Class axBalCmd extends RmttBase {

    public $x_Product;
    public $x_utcDateTime;
    public $x_Checksum;
    public $r_ReferenceID;
    public $r_ReturnCode;
    public $r_ReturnMessage;
    public $r_TransactionID;
    public $r_CurrencyType;
    public $r_BusinessAccount;
    public $r_FeeAccount;

} //end of axBalCmd class

Class axStatusCmd extends RmttBase {

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

} //end of axStatusCmd class


?>
