<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_dataObjCloseInp extends RODSPacket
{
    public function __construct($l1descInx = -1, $bytesWritten = 0)
    {
        $packlets = array("l1descInx" => $l1descInx,
            'bytesWritten' => $bytesWritten);
        parent::__construct("dataObjCloseInp_PI", $packlets);
    }

}
