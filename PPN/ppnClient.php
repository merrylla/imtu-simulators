<?php
include '../QAlib/soaplib.php';
$wsdl="https://devcall02.ixtelecom.com/wsdl/PPN.wsdl";

$client = new SoapClient($wsdl, array('trace'   => TRUE));

  //Execute
  $msgBA=array ('version' => '1.0',
                'skuId' => '275',
                'quantity' => '1',
                'corelationId' => '10123134333693206'
                 );

//  $msgB = array('PurchasePin' => $msgBA);

    $msgB = new SoapParam($msgBA, 'PurchasePin');

try {

//  $rsp= $client->__soapcall('PurchasePin', $msgB);

  $rsp=$client->PurchasePin($msgB);

  echo("\nDumping request:\n" . $client->__getLastRequest());
  echo("\nDumping response:\n".$client->__getLastResponse());
}
catch (Exception $e)
{
echo "CAUGHT AN EXCEPTION"."\n";
var_dump($e);
}
?>
