<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_CollInp extends RODSPacket
{
    public function __construct($collName = "",
                                RP_KeyValPair $KeyValPair_PI = NULL)
    {
        if (!isset($KeyValPair_PI)) $KeyValPair_PI = new RP_KeyValPair();

        $packlets = array("collName" => $collName,
            'KeyValPair_PI' => $KeyValPair_PI);
        parent::__construct("CollInp_PI", $packlets);
    }

}

?>