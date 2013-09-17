<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_DataObjCopyInp extends RODSPacket
{
    public function __construct(RP_DataObjInp $src = NULL,
                                RP_DataObjInp $dest = NULL)
    {
        if (!isset($src)) $src = new RP_DataObjInp();
        if (!isset($dest)) $dest = new RP_DataObjInp();

        $packlets = array("src" => $src, 'dest' => $dest);
        parent::__construct("DataObjCopyInp_PI", $packlets);
    }

}
