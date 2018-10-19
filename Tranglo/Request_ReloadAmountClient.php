<?php
include '../QAlib/soaplib.php';
$wsdl="http://devcall02.ixtelecom.com/wsdl/Tranglo_EPin_Reload.wsdl";

$client = new SoapClient($wsdl, array('trace'   => TRUE));

    $msgB=array('sourceNo' => '46840127',
               'destNo' => '46840127',
               'prodCode' => 'SM',
               'deno' => '26',
               'UserID' => 'idt_test',
               'Password' => '181358',
               'transID' => '101018134616521502'
               );

try {

  $rsp=$client->Request_ReloadAmount($msgB);

  echo("\nDumping request:\n" . $client->__getLastRequest());
  echo("\nDumping response:\n".$client->__getLastResponse());
}
catch (Exception $e)
{
echo "CAUGHT AN EXCEPTION"."\n";
var_dump($e);
}
?>
