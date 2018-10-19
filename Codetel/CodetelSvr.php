<?php
$fn=preg_replace('/\.php/', '', basename(__FILE__));

include '../ezetop/ImtuTopUpClass.php';
include '../QAlib/wslib1.php';
$wsdl = "http://devcall02.ixtelecom.com/wsdl/Codetel.wsdl";

date_default_timezone_set('America/New_York');


function setErrMsg($err) {
      /*
1     -The credit limit is not enought to make this transaction. 
       -User is not allowed to make transactions.
200 SQL exception when trying to insert the transaction in the database.
300 Exception returned by the prepaid platform.
400 Currency code does not exist.
500 The transaction amount is not within the allowed range.
600 thereâ no ranges defined for this currency.
700 Excepción retuned bye the webservice
*/ 
     $errMsg = "The credit limit is not enought to make this transaction.";
  return $errMsg;
}

class SoapHandler {

function REX_PROCESA_RECARGA($p) {

     errlog("REX_PROCESA_RECARGA p = ", $p);
     $distCode = $p->Codigo_Distribuidor;
     $branchCode =$p->Codigo_Canal;
     $phone = $p->Telefono;
     $actDate = $p->MSD; // format is yyyymmdd
     $transNum = $p->No_Transaccion;
     $currCode = $p->Codigo_Moneda;
     $transAmt = $p->Monto_Transaccion;
     $paymentCode = $p->Codigo_Pago; //001 = Debit card, 002 = Cash, 003=CreditCaard
     $status = $p->Sigla_Estatus; //c (create) a(active) s (suspended)
     errlog("REX_PROCESA_RECARGA status= ", $status);
     $key=array('app' => 'Codetel', 'key' => $phone);
     $keyT=array('app' => 'Codetel', 'key' => $transNum);

     $xA = array('phone' => 'a0',
                  'transNum' => 'a1',
                  'currCode' => 'a2',
                  'transAmtLocal' => 'a3',
                  'transAmtUSD' => 'a4',
                  'transAmtDOP' => 'a5', //in Dominican Pesos
                  'errCode' => 'a4',
                  'errMsg' => 'a5');
     errlog("REX_PROCESA_RECARGA xA =", $xA);
     $rA = array('phone' => 'a0',
                  'transNum' => 'a1',
                  'currCode' => 'a2',
                  'transAmtLocal' => 'a3',
                  'transAmtUSD' => 'a4',
                  'transAmtDOP' => 'a5', //in Dominican Pesos
                  'errCode' => 'a4',
                  'errMsg' => 'a5');
     $xArr = array('a0' => $phone,
                'a1' => $transNum,
                'a2' => $currCode,
                'a3' => 'Ok');
     updateTransDBRec($phone, $xArr, 'Codetel');
     insertTransDB($keyT, $xArr);
     errlog("REX_PROCESA_RECARGA xArr =", $xArr);
     $tpObj= new TopupObj($key, $xA, $rA);
     errlog("REX_PROCESA_RECARGA tpObj = ", $tpObj);
     if ($tpObj->re[errCode]) {
        $error = (int) $tpObj->re[errCode];
        $errMsg = setErrMsg($error);
        errlog("REX_PROCESA_RECARGA set errMsg & errCode= $error", $errMsg);
     //$rspA = array('DoTopUpResult' =>  
        } else {
        $error = 0;
        $errMess = "Transaccion Completada Satisfactoiamente";
        }
        //$massagedInput = array(
       $defSchema = "<xs:schema id=\"NewDataSet\" xmlns:xs=\"http://www.w3.org/2001/XMLSchema\" xmlns:msdata=\"urn:schemas-microsoft-com:xml-msdata\">";
       $defSchema += "<xs:element name=\"NewDataSet\" msdata:IsDataSet=\"true\" msdata:UseCurrentLocale=\"true\">";
       $defSchema += "<xs:complexType>";
       $defSchema += "<xs:choice minOccurs=\"0\" maxOccurs=\"unbounded\">";
       $defSchema += "<xs:element name=\"Table\"><xs:complexType><xs:sequence>";
       $defSchema += "<xs:element name=\"Codigo\" type=\"xs:int\" minOccurs=\"0\"/>";
       $defSchema += "<xs:element name=\"Descripcion\" type=\"xs:string\" minOccurs=\"0\"/>";
       $defSchema += "<xs:element name=\"Codigo_Moneda\" type=\"xs:string\" minOccurs=\"0\"/>";
       $defSchema += "<xs:element name=\"Monto_Moneda_Extranjera\" type=\"xs:decimal\" minOccurs=\"0\"/>";
       $defSchema += "<xs:element name=\"Monto_RD\" type=\"xs:decimal\" minOccurs=\"0\"/>";
       $defSchema += "</xs:sequence></xs:complexType></xs:element></xs:choice>";
       $defSchema += "</xs:complexType></xs:element></xs:schema>";     
       $rspM = array(
           'Codigo' => $error,
           'Descripcion' => $errMess,
	   'Condigo Monedo' => $currCode,
           'Monto_Moeda_Extranjera' => (float) $transAmt,
	   'Monto_RD' => $transAmt*44.6380);
 
     errlog("REX_PROCESA_RECARGA rspM= ", $rspM);
     return array('REX_PROCESA_RECARGAResponse' =>
              array('REX_PROCESA_RECARGAResult' => $defSchema,
                     newSoapVar ('<diffgr:diffgram xmlns:msdata=\"urn:schemas-microsoft-com:xml-msdata\" xmlns:diffgr\="urn:schemas-microsoft-com:xml-diffgram-v1\'"),
		     newSoapParam(NewDataSet, 
                     array('Table diffgr:id=\"Table1\" msdata:rowOrder=\"0\">',$rspM, '</Table> </NewDataSet> </diffgr:diffgram>'); 
}

function REX_REVERSO_RECARGA($params) {

    errlog("REX_REVERSO_RECARGA p = ", $p);
     $distCode = $p->Codigo_Distribuidor;
     $branchCode =$p->Codigo_Canal;
     $phone = $p->Telefono;
     $actDate = $p->MSD; // format is yyyymmdd
     $transNum = $p->No_Transaccion;
     $currCode = $p->Codigo_Moneda;
     $transAmt = $p->Monto_Transaccion;
     $paymentCode = $p->Codigo_Pago; //001 = Debit card, 002 = Cash, 003=CreditCaard
    $rspM = array();

     $rspM->REX_REVERSO_RECARGAResult= new stdClass;
     $rspM->REX_PROCESA_RECARGAResult->schema = "<schema> This is temp text -  123434343</schema>";
     $rspM->REX_PROCESA_RECARGAResult->any = "ANYTEXT"; 
$rspM->REX_PROCESA_RECARGAResult->schema = 1;
$rspM->REX_PROCESA_RECARGAResult->any = 2;*/

     return $rspM;

}//REX_REVERSO_RECARGA */


} //SoapHandler 


     errlog("HTTP_RAW_POST_DATA = ", $HTTP_RAW_POST_DATA);
//$data= file_get_contents('php://input');
//error_log("INPUT stream: $data\n\n");

$server = new SoapServer($wsdl,
                array(//'uri' => "http://172.27.17.24/wsRex/service.asmx",
                      'soap_version' => SOAP_1_2,
                      'trace' => 1));

$server->setClass("SoapHandler");
     errlog("set up server class = ", $wsdl);
$server->handle();
errlog("handled:", $HTTP_RAW_POST_DATA);

?>
