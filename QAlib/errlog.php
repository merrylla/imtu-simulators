<?php
function errlog($modName, $msgAnyType, $eNum=0)
{
     global $fn;

     if (gettype($msgAnyType) == 'string') {
        $errMsg=&$msgAnyType;
     }
     else {
        $errMsg=mix2str($msgAnyType);
     }
     sLog($fn, $modName, $errMsg, $eNum);
}

?>
