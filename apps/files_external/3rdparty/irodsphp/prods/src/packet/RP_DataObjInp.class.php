<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_DataObjInp extends RODSPacket
{
    public function __construct($objPath = "", $createMode = 0, $openFlags = 0,
                                $offset = 0, $dataSize = -1, $numThreads = 0, $oprType = 0,
                                RP_KeyValPair $KeyValPair_PI = NULL)
    {
        if (!isset($KeyValPair_PI)) $KeyValPair_PI = new RP_KeyValPair();

        $packlets = array("objPath" => $objPath, 'createMode' => $createMode,
            'openFlags' => $openFlags, 'offset' => $offset, "dataSize" => $dataSize,
            "numThreads" => $numThreads, "oprType" => $oprType,
            'KeyValPair_PI' => $KeyValPair_PI);
        parent::__construct("DataObjInp_PI", $packlets);
    }

}
