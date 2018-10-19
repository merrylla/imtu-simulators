<?php
include '../QAlib/soaplib.php';
$wsdl="https://devcall02.ixtelecom.com/wsdl/awsvrecarga.aspx.wsdl";

$client = new SoapClient($wsdl, array('trace'   => TRUE));

  //Execute
  $msgBA=array ('Venide' => 'IDT001',
                'Venpas' => 'IDT001',
                'Paicod' => 'CU',
                'Opecod' => 'CU',
                'Telnum' => '5353663892',
                'Valor' => '26',
                'Recfee' => '0',
                'Recrem' => '123456',
                'RechargeType' => 1      );

//  $msgB = array('Execute' => $msgBA);
 
    $msgB = new SoapParam($msgBA, 'Execute');
  
try {

//  $rsp= $client->__soapcall('Execute', $msgB);

  $rsp=$client->Execute($msgB);

  echo("\nDumping request:\n" . $client->__getLastRequest());
  echo("\nDumping response:\n".$client->__getLastResponse());
}
catch (Exception $e) 
{
echo "CAUGHT AN EXCEPTION"."\n";
var_dump($e);
}
?>
