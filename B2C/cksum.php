<?php

 $str='b2cQAuser|101181136252154808|192|2|3756815080|2800|b2cQAtest'; 

 //$checksum = sha1(md5($str));
 $checksum = md5(sha1($str));

  echo $checksum;

?>
