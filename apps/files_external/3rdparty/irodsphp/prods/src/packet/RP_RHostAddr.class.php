<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_RHostAddr extends RODSPacket
{
    public function __construct($hostAddr = '', $rodsZone = '', $port = 0)
    {
        $packlets = array("hostAddr" => $hostAddr, "rodsZone" => $rodsZone,
            "port" => $port);
        parent::__construct("RHostAddr_PI", $packlets);
    }

}
