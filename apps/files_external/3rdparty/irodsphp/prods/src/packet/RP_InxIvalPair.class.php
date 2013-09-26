<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_InxIvalPair extends RODSPacket
{
    public function __construct($iiLen = 0, array $inx = array(), array $ivalue = array())
    {
        $packlets = array("iiLen" => $iiLen, 'inx' => $inx, 'ivalue' => $ivalue);
        parent::__construct("InxIvalPair_PI", $packlets);
    }

    public function fromAssocArray($array)
    {
        if (!empty($array)) {
            $this->packlets["iiLen"] = count($array);
            $this->packlets["inx"] = array_keys($array);
            $this->packlets["ivalue"] = array_values($array);
        } else {
            $this->packlets["iiLen"] = 0;
            $this->packlets["inx"] = array();
            $this->packlets["ivalue"] = array();
        }
    }

}
