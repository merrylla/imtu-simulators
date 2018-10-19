<?php

$typeArray=array();

function getWsdlType($wsdlurl)
{
$client = new SoapClient($wsdlurl);
return $client->__getTypes();
}

function getWsdlFunc($wsdlurl)
{
$client = new SoapClient($wsdlurl);
return $client->__getFunctions();
}

function buildFuncArray($wsdlurl)
{
    $Func = getWsdlFunc($wsdlurl);

    foreach ($Func as $value) {
       preg_match_all("/\b\w+\b/", $value, $tmp, PREG_PATTERN_ORDER);
       
       $fname = $tmp[0][1];
       $fArray[$fname] = array('input' => $tmp[0][2], 'output' =>  $tmp[0][0]);
    }
    return $fArray;
}

function buildTypeArray($wsdlurl)
{
    $Types = getWsdlType($wsdlurl);

    foreach ($Types as $value) {
       preg_match_all("/\b\w+\b/", $value, $tmp, PREG_PATTERN_ORDER);
       $tArray[]=$tmp[0];
    }
    return $tArray;
}

function disectVarType($Type)
/*
 * construct the array structure for input $Type base on global $typeArray. 
 *
*/
{
    global $typeArray;

    foreach ( $typeArray as  $value) {

        $elecount = sizeof($value);

        if (strcmp($value[1], $Type) ==0 ) { 

              if ($elecount == 2) return $value[0];
              else {
                   $element=array();
                   for ($i=2; $i< $elecount; $i=$i+2) {
                       $element[$value[$i+1]] = disectVarType($value[$i]);  
                   }
                   return $element;
               } // digging deeper into type

       } // found match 
    }//end loop to search in $typeArray 

    return $Type;
}

function popX2A (&$aNode, $xNode)
{
   foreach($aNode as $key => $value) {
       if (gettype($value) == "array") 
        {
          $tag = $xNode->getElementsByTagName($key)->item(0);
          if (isset($tag)) popX2A($aNode[$key], $xNode);
          else {
             // tag not found
             unset($aNode[$key]);
             continue;
           }
        } 
       else 
        {
            //found tag
          $tag = $xNode->getElementsByTagName($key)->item(0);
          if (isset($tag)) {
//echo "debug: ",$key, "  xml node ", $tag->nodeValue,"\n"; 
            $aNode[$key]= $tag->nodeValue;
            }
          else {
            unset($aNode[$key]);
          }
       }
   }// foreach loop
}

function popA2X(&$doc, &$node, $type)
{
   if (gettype($type) != "array") return;

   foreach($type as $key => $value) {
       if (gettype($value) == "array") $newE=$doc->createElement($key);
       else
           $newE=$doc->createElement($key, $value);

       $child=$node->appendChild($newE);

       popA2X($doc, $child, $value);
   }
}

function buildXml($type) {
/*
 * $type has to be a type array returned by buildMsgArray($msgtype, $wsdl)
 */
//error_log("INFO: buildXml call \n");
//$doc=new DOMDocument('1.0', 'UTF-8');
$doc=new DOMDocument('1.0', 'ISO-8859-1');

/*
$newE= $doc->createElement("SOAP-ENV:Envelop");
$rootNode= $doc->appendChild($newE);
$newE= $doc->createElement("SOAP-ENV:Body");
$rootNode= $doc->appendChild($newE);
*/

popA2X($doc, $doc, $type);
return $doc->saveXML($doc, LIBXML_NOEMPTYTAG);
} //buildXml


function buildMsgArray($msgType, $url)
{
global $typeArray;

$typeArray = buildTypeArray($url);

return disectVarType($msgType);  

//return $msg;

} //buildWsdlFunc

function soapSend($wsdl, $method, $msg)
{
    $client = new SoapClient($wsdl, array('trace'   => TRUE));

    $rsp=$client->__soapcall($method, $msg);

//$rsp is a resp array
    echo("\nDumping request:\n" . $client->__getLastRequest());
//    echo ("\nDumping resp:\n" .$client->__getLastResponse() );

   return $client->__getLastResponse();
}

?>
