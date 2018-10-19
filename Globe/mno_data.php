<?php

Class MnoData extends RmttBase {
    
    public $TestCase = "TC01 - Normal Transfer" ;
    public $CreationDate ;
    public $LastUpdate ;
    public $UserID = "Leo" ;
    public $ExpAccessCount=2 ;
    public $AccessCount ;
    public $state ;
    public $RemittanceBalance ;
    public $PassedTragetCommand="commitcashcmd";
    public $PassFail ;
    public $Comment ;
    public $EmailNotification="leo.mak@idt.net";
    
    public $MNO_ID = "GLOBE IDT INT";
    public $IDT_INIT_BAL = "1000";
    public $IDT_BALANCE ;
    
    public $PaymentAccountNumber ="09010560001";
    
    public $remitterfirstname ;
    public $remitterlastname ;
    public $remitteraddress ;
    public $remitterphone ;
    public $PaymentAmount ;
    public $ExpPaymentAmount ;
   
    public $cashincmdSessionRC ;
    public $cashincmdRetry ;
    public $cashincmdRC ;
    public $cashincmdRC1 ;
    public $cashincmdRC2 ;
    public $cashincmdRC3 ;
    public $cashincmdTransactionCode ;
    public $cashincmdGcash ;
    public $cashincmdState ;
    public $cashincmdLastUpdate ;
    
    public $commitcashcmdSessionRC ;
    public $commitcashcmdRetry ;
    public $commitcashcmdRC ;
    public $commitcashcmdRC1 ;
    public $commitcashcmdRC2 ;
    public $commitcashcmdRC3 ;
    public $commitcashcmdTransactionCode ;
    public $commitcashcmdState ;
    public $commitcashcmdLastUpdate ;
    
    public $balancecmdSessionRC ;
    public $balancecmdRetry ;
    public $balancecmdRC ;
    public $balancecmdRC1 ;
    public $balancecmdRC2 ;
    public $balancecmdRC3 ;
    public $balancecmdTransactionCode ;
    public $balancecmdBalance ;
    public $balancecmdState ;
    public $balancecmdLastUpdate ;
    
    public $rbcashcmdSessionRC ;
    public $rbcashcmdRetry ;
    public $rbcashcmdRC ;
    public $rbcashcmdRC1 ;
    public $rbcashcmdRC2 ;
    public $rbcashcmdRC3 ;
    public $rbcashcmdTransactionCode ;
    public $rbcashcmdState ;
    public $rbcashcmdLastUpdate ;

} //end of MnoData class
    
?>
