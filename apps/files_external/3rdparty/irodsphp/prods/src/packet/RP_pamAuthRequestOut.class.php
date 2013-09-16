<?php

require_once(dirname(__FILE__)."/../autoload.inc.php");
class RP_pamAuthRequestOut extends RODSPacket
{
  public function __construct($irodsPamPassword="")
  {
    $packlets=array("irodsPamPassword" => $irodsPamPassword);  
    parent::__construct("pamAuthRequestOut_PI",$packlets);
  }
     
}
