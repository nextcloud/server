<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_authRequestOut extends RODSPacket
{
    public function __construct($challenge = "")
    {
        $packlets = array("challenge" => $challenge);
        parent::__construct("authRequestOut_PI", $packlets);
    }

}
