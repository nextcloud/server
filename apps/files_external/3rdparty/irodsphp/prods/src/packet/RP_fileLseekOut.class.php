<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_fileLseekOut extends RODSPacket
{
    public function __construct($offset = 0)
    {
        $packlets = array("offset" => $offset);
        parent::__construct("fileLseekOut_PI", $packlets);
    }

}
