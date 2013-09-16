<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_Version extends RODSPacket
{
    public function __construct($status = 0, $relVersion = 'rods0.5',
                                $apiVersion = 'a')
    {
        $packlets = array("status" => $status, "relVersion" => $relVersion,
            "apiVersion" => $apiVersion);
        parent::__construct("Version_PI", $packlets);
    }

}
