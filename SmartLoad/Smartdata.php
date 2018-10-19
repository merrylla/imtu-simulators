<?php

Class SmartTopUp extends RmttBase {

     public $x_CorporateID;
     public $x_UserName;
     public $x_Password;
//    $x_DestinationNo -  this is same as msisdn stored under x_account_number in the DB
     public $x_account_number;
// map x_Amount to x_Denomination
     public $x_Amount;
// map r_Result to r_SendSmartLoadResult  
     public $r_Result;
     public $delay;
     public $state;
}
