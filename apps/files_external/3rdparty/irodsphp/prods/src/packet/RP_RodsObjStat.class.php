<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_RodsObjStat extends RODSPacket
{
    public function __construct($objSize = 0, $objType = 0, $numCopies = 0,
                                $dataId = -1, $chksum = NULL, $ownerName = NULL, $ownerZone = NULL,
                                $createTime = NULL, $modifyTime = NULL)
    {
        $packlets = array("objSize" => $objSize, 'objType' => $objType,
            'numCopies' => $numCopies, 'dataId' => $dataId, "chksum" => $chksum,
            "ownerName" => $ownerName, "ownerZone" => $ownerZone,
            'createTime' => $createTime, 'modifyTime' => $modifyTime);
        parent::__construct("RodsObjStat_PI", $packlets);
    }

}
