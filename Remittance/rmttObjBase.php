<?php

Class RmttBase {

  function setPrefix($name) {
         return $this->dbPrefix = $name;
  }

  function setElement($dbname, $value, $dbprefix=NULL)
  {
          foreach ($this as $key => $dummy) {
            if (strcasecmp($dbname, "$dbprefix$key")==0) $this->$key = $value;
            }
          return;
  }

  function getPrefix() {

       return NULL;
  }

} //RmttBase
?>
