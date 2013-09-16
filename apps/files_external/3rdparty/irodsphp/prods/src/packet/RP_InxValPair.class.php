<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
require_once(dirname(__FILE__) . "/../RodsGenQueryNum.inc.php");

class RP_InxValPair extends RODSPacket
{
    public function __construct($isLen = 0, array $inx = array(), array $svalue = array())
    {
        $packlets = array("isLen" => $isLen, 'inx' => $inx, 'svalue' => $svalue);
        parent::__construct("InxValPair_PI", $packlets);
    }

    public function fromAssocArray($array)
    {
        if (!empty($array)) {
            $this->packlets["isLen"] = count($array);
            $this->packlets["inx"] = array_keys($array);
            $this->packlets["svalue"] = array_values($array);
        } else {
            $this->packlets["isLen"] = 0;
            $this->packlets["inx"] = array();
            $this->packlets["svalue"] = array();
        }
    }

    public function fromRODSQueryConditionArray($array)
    {
        $this->packlets["isLen"] = 0;
        $this->packlets["inx"] = array();
        $this->packlets["svalue"] = array();

        if (!isset($array)) return;

        $this->packlets["isLen"] = count($array);
        foreach ($array as $cond) {
            $this->packlets["inx"][] = $cond->name;
            $this->packlets["svalue"][] = "$cond->op '$cond->value'";
            //echo "<pre> $cond->op '$cond->value' </pre>";
        }
    }
}
