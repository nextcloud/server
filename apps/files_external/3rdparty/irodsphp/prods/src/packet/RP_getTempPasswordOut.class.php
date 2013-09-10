<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_getTempPasswordOut extends RODSPacket
{
    public function __construct($stringToHashWith = '')
    {
        $packlets = array("stringToHashWith" => $stringToHashWith);
        parent::__construct("getTempPasswordOut_PI", $packlets);
    }

}
