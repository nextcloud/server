<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_MiscSvrInfo extends RODSPacket
{
    public function __construct($serverType = 0, $relVersion = 0, $apiVersion = 0,
                                $rodsZone = '')
    {
        $packlets = array("serverType" => $serverType, 'relVersion' => $relVersion,
            'apiVersion' => $apiVersion, 'rodsZone' => $rodsZone);
        parent::__construct("MiscSvrInfo_PI", $packlets);
    }

}
