<?php

//idev/r1300/base/baseincl/imsglib.h
//idev/r1310/base/baseincl/imsglib.h:#define IMSG_SENDERID_LEN 16
//idev/r1310/base/baseincl/imsglib.h:#define IMSG_DBROUTEINFO_LEN 21

//sample decoded header by itopupgateway
//imtuReq: len=412;ver=1310;msgty=25;reqty=110;coor=5;msgfmt=3;dbrtit=0;dbrtin=1;
//         dbrtsts=0;dbver=5;sndr=/u/ixc/sendImsg

$iMsgHdrSz=80;  //pickup from imsglib.h

$sndMsg='<?xml version="1.0" encoding="ISO-8859-1"?><msg><control> <cmd>topup</cmd> <user>WEB</user> <usertype>WEB_BR</usertype> </control> <qryparams> <countrycode>Philippines</countrycode> <carrierName>Smart</carrierName> <msisdn>09173367890</msisdn> <topupAni>9085551000</topupAni> <topupPin>83746842749</topupPin> </qryparams> </msg>';

class IMsgHeader {
      public $length;
      public $msgVersion=1310;
      public $msgType=25;   //IMSG_TYPE_INTL_MOBILE_TOPUP
      public $reqType=110;  
      public $corrId=5;
      public $msgFormat=3;
      public $dbVersion=5;
      public $dbRouteInfoType=0; //IMSG_DBROUTE_INFO_TYPE_NONE
      public $dbRouteStatus=0;   //IMSG_DBROUTE_STATUS_MINVALUE
      public $distrId=0;
      public $senderId="QAphpSendImsgDr.";  //has to be 16 bytes
      public $dbRouteInfo="InstrToDistrToRoute22..."; // has to be 24 bytes

      function IMsgHeader() 
      {
          global $sndMsg, $iMsgHdrSz;

          $hdrSz=10*4+strlen($this->senderId)+strlen($this->dbRouteInfo);

          if ( $hdrSz != $iMsgHdrSz) die("ERROR: imsg header size expect $$iMsgHdrSz got $hdrSz");
          $this->length=$hdrSz+strlen($sndMsg);
       }
} 

class IMsg {
      public $header;
      public $mbody;

      function IMsg() 
      {
           global $sndMsg;
           $this->header = new IMsgHeader;
           $this->mbody=$sndMsg;
      }
      function pack() 
      {
           echo "DEBUG: header legth",$this->header->length,"BodySize: ", strlen($this->mbody),"\n";
//            return pack("i10a*a*a*", $this->header->length,  
            return pack("N10a*a*a*", $this->header->length,  
                                  $this->header->msgVersion,
                                  $this->header->msgType,
                                  $this->header->reqType,
                                  $this->header->corrId,
                                  $this->header->msgFormat,
                                  $this->header->dbVersion,
                                  $this->header->dbRouteInfoType,
                                  $this->header->dbRouteStatus,
                                  $this->header->distrId,
                                  $this->header->senderId,
                                  $this->header->dbRouteInfo,
                                  $this->mbody);
      }
}

$fp = stream_socket_client("tcp://qaweb01.ixtelecom.com:9932", $errno, $errstr, 30);
if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
    $snd=new IMsg;

    fwrite($fp, $snd->pack());
    echo "Query: ",$snd->mbody,"\n";


   if (($msgHdr=fread($fp, $iMsgHdrSz))==FALSE) die("ERROR: failed to get response message header");

   $rA = unpack("N10a*a*",$msgHdr);

   $MsgSz=array_shift($rA); 
//echo"Received msgHdr: ",bin2hex($msgHdr),"\n";
//var_dump($rA);

    echo "Response: \n",fread($fp, $MsgSz-$iMsgHdrSz),"\n";

    fclose($fp);
}
?>
