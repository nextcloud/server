<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_MsgHeader extends RODSPacket
{
    public function __construct($type = NULL, $msgLen = 0, $errorLen = 0, $bsLen = 0,
                                $intInfo = 0)
    {
        $packlets = array("type" => $type, "msgLen" => $msgLen,
            "errorLen" => $errorLen, "bsLen" => $bsLen, "intInfo" => $intInfo);
        parent::__construct("MsgHeader_PI", $packlets);
    }

}
