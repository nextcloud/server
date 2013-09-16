<?php

require_once(dirname(__FILE__)."/../autoload.inc.php");
class RP_pamAuthRequestInp extends RODSPacket
{
  public function __construct($pamUser="", $pamPassword="", $timeToLive=-1)
  {
    $packlets=array("pamUser" => $pamUser, "pamPassword" => $pamPassword, "timeToLive" => $timeToLive);  
    parent::__construct("pamAuthRequestInp_PI",$packlets);
  }
     
}
