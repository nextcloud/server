<?php

require_once(dirname(__FILE__)."/../autoload.inc.php");
class RP_sslEndInp extends RODSPacket
{
  public function __construct($arg0="")
  {
    $packlets=array("arg0" => $arg0);  
    parent::__construct("sslEndInp_PI",$packlets);
  }
     
}
