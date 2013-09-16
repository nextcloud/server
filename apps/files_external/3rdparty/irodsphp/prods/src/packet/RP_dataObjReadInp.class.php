<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_dataObjReadInp extends RODSPacket
{
    public function __construct($l1descInx = -1, $len = 0)
    {
        $packlets = array("l1descInx" => $l1descInx,
            'len' => $len);
        parent::__construct("dataObjReadInp_PI", $packlets);
    }

}
