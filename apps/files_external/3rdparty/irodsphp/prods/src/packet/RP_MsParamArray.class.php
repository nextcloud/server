<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_MsParamArray extends RODSPacket
{
    // if one of the packlet is an array of packets, define it here.
    protected $array_rp_type;

    public function __construct(array $MsParam_PI = array(),
                                $oprType = 0)
    {
        $this->array_rp_type = array("MsParam_PI" => "RP_MsParam");

        $packlets = array("paramLen" => count($MsParam_PI),
            "oprType" => $oprType, "MsParam_PI" => $MsParam_PI);
        parent::__construct("MsParamArray_PI", $packlets);
    }

}
