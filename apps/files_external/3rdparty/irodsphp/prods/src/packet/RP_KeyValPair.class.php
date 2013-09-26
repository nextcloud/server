<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_KeyValPair extends RODSPacket
{
    public function __construct($ssLen = 0, array $keyWord = array(), array $svalue = array())
    {
        if ($ssLen < 1) {
            $keyWord = NULL;
            $svalue = NULL;
        }

        $packlets = array("ssLen" => $ssLen, 'keyWord' => $keyWord,
            'svalue' => $svalue);
        parent::__construct("KeyValPair_PI", $packlets);
    }

    public function fromAssocArray(array $array)
    {
        if (!empty($array)) {
            $this->packlets["ssLen"] = count($array);
            $this->packlets["keyWord"] = array_keys($array);
            $this->packlets["svalue"] = array_values($array);
        } else {
            $this->packlets["ssLen"] = 0;
            $this->packlets["keyWord"] = array();
            $this->packlets["svalue"] = array();
        }
    }

    public function fromRODSQueryConditionArray($array)
    {
        $this->packlets["ssLen"] = 0;
        $this->packlets["keyWord"] = array();
        $this->packlets["svalue"] = array();

        if (!isset($array)) return;

        $this->packlets["ssLen"] = count($array);
        foreach ($array as $cond) {
            $this->packlets["keyWord"][] = $cond->name;
            $this->packlets["svalue"][] = "$cond->op '$cond->value'";
        }
    }
}
