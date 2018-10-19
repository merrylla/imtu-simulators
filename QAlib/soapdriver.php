#!/usr/local/bin/php
<?php

include '../QAlib/soaplib.php';

//$wsdl="http://devcall02.ixtelecom.com/wsdl/RemittanceClearingHouse.wsdl";
//$wsdl="http://lwebdev01.ixtelecom.com:7100/paymasterRCHService/WSDLpp";
//$wsdl="http://lwebdev01.ixtelecom.com:7100/paymasterRCHService?wsdl";
$wsdl="http://devcall02.ixtelecom.com/wsdl/Orange.wsdl";

$cmd = 'cmd';

if (isset($argv[1]) ) {

   if ( preg_match('/show/i', $argv[1]) ) $cmd='show';
   else
       $cmd='cmd';

   $showCmd=NULL;

   switch($cmd) {
     case 'show':
         if (preg_match('/func/i', $argv[1])) var_dump (buildFuncArray($wsdl));
         else
         if (preg_match('/type/i', $argv[1])) var_dump( buildTypeArray($wsdl));
         else
         if (preg_match('/queryBy.*org/i', $argv[1])) $showCmd='RemittanceQueryByOriginatorTransactionId';
         else
         if (preg_match('/query.*resp/i', $argv[1])) $showCmd='RemittanceQueryResponse';
         else
         if (preg_match('/query/i', $argv[1])) $showCmd='RemittanceQuery';
         else
         if (preg_match('/cancelBy.*or/i', $argv[1])) $showCmd='RemittanceCancelByOriginatorTransactionId';
         else
         if (preg_match('/cancel.*resp/i', $argv[1])) $showCmd='RemittanceCancelResponse';
         else
         if (preg_match('/cancel/i', $argv[1])) $showCmd='RemittanceCancel';
         else
         if (preg_match('/trfresp|transresp/i', $argv[1])) $showCmd='RemittanceTransferResponse';
         else
         if (preg_match('/trf|trans/i', $argv[1])) $showCmd='RemittanceTransfer';


         if ($showCmd) {
             $type[$showCmd] = buildMsgArray($showCmd , $wsdl);

             if (preg_match('/arr/i', $argv[1])) var_dump($type);
             else 
                 echo buildXml($type);
         }
         else echo "Error: ", $argv[1], " command not found","\n";
         exit(0);

     case 'cmd':

         if (preg_match('/trf|trans/i', $argv[1])) $showCmd='RemittanceTransfer';
         else
         if (preg_match('/queryBy/i', $argv[1])) $showCmd='RemittanceQueryByOriginatorTransactionId';
         else
         if (preg_match('/query/i', $argv[1])) $showCmd='RemittanceQuery';
         else
         if (preg_match('/cancelBy.*or/i', $argv[1])) $showCmd='RemittanceCancelByOriginatorTransactionId';
         else
         if (preg_match('/cancel/i', $argv[1])) $showCmd='RemittanceCancel';

         $cmd = $showCmd;

         if ($showCmd) break;

     default:
         echo  "Usage: ". $argv[0] . "RCHmethod [ xmlfile ]\n";
         exit();
}
}// end if

echo "CMD: ",$cmd,"\n";

try {

$xmlfile = "../QAdata/".$cmd.".xml";

$xfer=new DOMDocument;
$xfer->load($xmlfile, LIBXML_NOBLANKS);

$rchws[$cmd] = buildMsgArray($cmd, $wsdl); 
popX2A($rchws[$cmd], $xfer);

echo soapSend($wsdl, $cmd, $rchws);

die("exiting!\n");
}
catch (SoapFault $exception) {
    echo $exception;
}
?>
