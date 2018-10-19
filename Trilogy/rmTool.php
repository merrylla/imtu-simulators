<?php

define('XMLD', "<?xml version='1.0' encoding='UTF-8'?>");
define('NSENV', " xmlns:env='http://www.w3.org/2003/05/soap-envelope'");
define('NSRM', " xmlns:r='http://schemas.xmlsoap.org/ws/2005/02/rm'");
define('NSADR', " xmlns:a='http://www.w3.org/2005/08/addressing'");
define('NSVO', " xmlns:ns1='http://schemas.datacontract.org/2004/07/Voila.VoilaWS'");


function getMultiPmsg($fn='php://stdin')
{
     $data='';
     $fd=fopen($fn, 'r');
     $data .=stream_get_contents($fd, 650);

     $matches=array();
     preg_match("/\r\n(--.*)\r\n.*/", $data, $matches);
     $bound=$matches[1].'--';

     $data .=stream_get_contents($fd, 650);

     return $data;
}

function getDataL($fn='php://stdin', $len=300)
{
     $fd=fopen($fn, 'r');
     $data='';
     $data .=stream_get_contents($fd, $len);

     return $data;
}

//function getDataM($fn='php://stdin', $pat='==--')
function getDataM($fn='php://stdin', $pat='--.{52,64}--')
{
     $fd=fopen($fn, 'r');
     $data='';
     $c='';
     while (!feof($fd)) {
       $c=stream_get_contents($fd, 1);
       $data .=$c;
       if (preg_match("%$pat%", $data)) break;
     }
     return $data;
}

Class RMobj {

    public $state; //createSeq, sequenceRsp, sequenceProc, sequenceAck, cancel, terminate

    public $hdr;
    public $bdy='';

    public $Offer;
    public $Sequence;

    public $env1;
    public $env2="</env:Envelope>";
    public $hd1="<env:Header>";
    public $hd2="</env:Header>";
    public $bd1="<env:Body>";
    public $bd2="</env:Body>";

    //for create sequence
    //hdr - MessageID, ReplyTo, To, Action
    //bdy - CreateSequence

    public $cmd;

   //for RunTopUpSequence
   //hdr - MessageID, To, Action, Sequence

    function RMobj($m) {
        $this->setup($m);

        //errlog("INFO: RMobj: ", $this);
    }

    function setup($m) {

       $this->msg=$m;

       $this->env1=XMLD."<env:Envelope".NSENV.NSRM.NSADR.">";

       $rA=retrievAllXmlElem("Header", $m);

       foreach ($rA as $key => $v) 
            $this->hdr[$key] = $v;

       $this->cmd = preg_replace("/^.*\/(.*)$/s", '$1', $rA['Action']);

       if ($this->cmd == 'CreateSequence') {

           $rA=retrievAllXmlElem("Offer", $m);
           foreach ($rA as $key => $v)
             $this->offer[$key] = $v;
       }
       if ($this->cmd == 'RunTopUp') {

           $rA=retrievAllXmlElem("Sequence", $m);
           foreach ($rA as $key => $v)
             $this->Sequence[$key] = $v;

           //$this->bdy=preg_replace('/^.*(<SOAP-{0,1}ENV:Body>.*SOAP-{0,1}ENV:Body>).*$/is', '$1', $m);
           $this->bdy=preg_replace('/^.*<SOAP-{0,1}ENV(:Body>.*)SOAP-{0,1}ENV:Body>.*$/is', "<soapenv$1soapenv:Body>", $m);
       }

       //$bdyA=retrievAllXmlElem("Body", $m);
       //foreach ($bdyA as $key => $v) 
       //     $this->hdr[$key] = $v;
    }

   function crSeqAck() {

       $uuid="urn:uuid:".gen_uuid();
       $mid="<a:MessageID>$uuid</a:MessageID>";
       //$act="<a:Action env:mustUnderstand='true'>http://schemas.xmlsoap.org/ws/2005/02/rm/CreateSequenceResponse</a:Action>";
       $act="<a:Action>http://schemas.xmlsoap.org/ws/2005/02/rm/CreateSequenceResponse</a:Action>";

       $p=$this->hdr['To'];
       $rpto="<a:ReplyTo env:mustUnderstand='true'>$p</a:ReplyTo>";
       $p='';
       if (isset($this->hdr['ReplyTo'])) $p=$this->hdr['ReplyTo'];
       $to='';
       if ($p!='') $to="<a:To env:mustUnderstand='true'>$p</a:To>";
       $rpto='';
       $relatesTo=''; 
       if (isset($this->hdr['MessageID'])) 
              $relatesTo='<a:RelatesTo>'.$this->hdr['MessageID'].'</a:RelatesTo>';
         

       $hd=$mid.$to.$rpto.$act.$relatesTo;

       $uuid="urn:uuid:".gen_uuid();
       $acksto="<r:AcksTo><a:Address>".$this->hdr['To']."</a:Address></r:AcksTo>";
       $bdy="<r:CreateSequenceResponse><r:Identifier>$uuid</r:Identifier><r:Accept>".$acksto."</r:Accept></r:CreateSequenceResponse>";

       $msg=$this->env1.$this->hd1.$hd.$this->hd2.$this->bd1.$bdy.$this->bd2.$this->env2; 
       $offerId=$this->offer["Identifier"];

errlog("crSeqAck key: $uuid data: ", $offerId);
       $this->saveContext($uuid, $offerId);
       return $msg;
   }//crSeqAck

   function SeqAck($ns, $appMsg) {
       $uuid=gen_uuid();
       $mid="<a:MessageID>urn:uuid:$uuid</a:MessageID>";
       //$p=$this->hdr['ReplyTo'];
       $p='TBDxxxxxx';
       $to="<a:To env:mustUnderstand='true'>$p</a:To>";

       $act="<a:Action>RunTopUpResult</a:Action>";

       $offerId=gen_uuid();
       $key=$this->Sequence['Identifier'];
//errlog("SeqAck offerId ", $key);
       $offerId=$this->retrieveContext($key);
       $sq="<r:Sequence><r:Identifier>$offerId</r:Identifier><r:MessageNumber>1</r:MessageNumber><r:LastMessage /></r:Sequence>";
//errlog("SeqAck Header ", $this->Sequence);
       $ack="<r:SequenceAcknowledgement><r:Identifier>".$this->Sequence['Identifier']."</r:Identifier><r:AcknowledgementRange Lower='1' Upper='1' /></r:SequenceAcknowledgement>";

       $relatesTo=''; 
       if (isset($this->hdr['MessageID']))
              $relatesTo='<a:RelatesTo>'.$this->hdr['MessageID'].'</a:RelatesTo>';

       $hd=$mid.$act.$relatesTo.$sq.$ack;
       $bd1=preg_replace('/>/', " $ns", $this->bd1);

       $msg=$this->env1.$this->hd1.$hd.$this->hd2.$bd1.$appMsg.$this->bd2.$this->env2; 
       return $msg;
   }//SeqAck

   function saveContext($key, $data) {
      $db=openSqliteDB();
      DBinsertRecord($db, 'Trilogy', $key, $data);
   }//saveContext

   function retrieveContext($key) {

      $db=openSqliteDB();
      $r=DBretrieveRecord($db, 'Trilogy', $key);
      DBdeleteRecord($db, 'Trilogy', $key);
      return $r;
   }

}//RMobj



//
// get all elements 
function retrievAllXmlElem($xnode, $xmlinput)
{
   $eA=array();
   $dom = new DOMDocument();
   if (!$dom->loadXML($xmlinput) ) return $rA;

   $ndom=$dom->getElementsByTagName($xnode)->item(0)->childNodes;
   
   foreach ($ndom as $node) {
     $nodeName=preg_replace("/.*:(.*)$/", '$1', $node->tagName);
     $eA[$nodeName]= $node->nodeValue;
   }

   return $eA;

}//retrievAllXmlElem


//$name is the node name
//$m is the xml string
function findXmlNode($name, $m)
{
    $r=preg_replace("/^.*$name(.*)<.*$name.*$/s", '$1', $m);
    return $r;
}

function gen_uuid() {
 $uuid = array(
  'time_low'  => 0,
  'time_mid'  => 0,
  'time_hi'  => 0,
  'clock_seq_hi' => 0,
  'clock_seq_low' => 0,
  'node'   => array()
 );

 $uuid['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
 $uuid['time_mid'] = mt_rand(0, 0xffff);
 $uuid['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
 $uuid['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
 $uuid['clock_seq_low'] = mt_rand(0, 255);

 for ($i = 0; $i < 6; $i++) {
  $uuid['node'][$i] = mt_rand(0, 255);
 }

 $uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
  $uuid['time_low'],
  $uuid['time_mid'],
  $uuid['time_hi'],
  $uuid['clock_seq_hi'],
  $uuid['clock_seq_low'],
  $uuid['node'][0],
  $uuid['node'][1],
  $uuid['node'][2],
  $uuid['node'][3],
  $uuid['node'][4],
  $uuid['node'][5]
 );

 return $uuid;
}//gen_uuid

?>
