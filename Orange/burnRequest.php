<?php
include '../QAlib/soaplib.php';
$wsdl="http://devcall02.ixtelecom.com/wsdl/Orange.wsdl";

$client = new SoapClient($wsdl, array('trace'   => TRUE));

  //authHeaderParam
  $msgHA = array ('UserName' => 'idttesting', 'Password' => 'idt77777');
  $header = new SoapHeader('NAMESPACE', 'authHeader', $msgHA);

  //burnParam
  $msgBA=array ('ServiceType' => 2,
                'SNwTrxId'    => '12334',
                'SNwId'       => 'idt',
                'SNwType'     => 'abc',
                'SCountryCode' => 1,
                'RCountryCode' => 223,
                'SendingBearer' => 'WEB',
                'RechargeType' => 1,
                'MSISDN2'      => '26046843279',
                'SNwTimeStamp' => '2012-03-27T19:44:34Z',
                'ReceivableValueSc' => 'abcde',
                'ReceivableId' => '986420103');


  $ns='java:com.wha.iah.pretups.ws';
  $msgSV = new SOAPVar($msgBA, SOAP_ENC_OBJECT, 'BurnParam', $ns);

  $msgB = array('burnParam' => $msgSV);
  
  var_dump($msgB);

try {
//  $client->__setSoapHeaders($header);

  $rsp= $client->__soapcall('burnRequest', $msgB);

  echo("\nDumping request:\n" . $client->__getLastRequest());
  echo("\nDumping response:\n".$client->__getLastResponse());
}
catch (Exception $e) 
{
echo "CAUGHT AN EXCEPTION"."\n";
var_dump($e);
}
?>
