<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_CollOprStat extends RODSPacket
{
    public function __construct($filesCnt = 0, $totalFileCnt = 0, $bytesWritten = 0,
                                $lastObjPath = '')
    {
        $packlets = array("filesCnt" => $filesCnt, "totalFileCnt" => $totalFileCnt,
            'bytesWritten' => $bytesWritten, 'lastObjPath' => $lastObjPath);
        parent::__construct("CollOprStat_PI", $packlets);
    }

}
