<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_ExecMyRuleInp extends RODSPacket
{
    public function __construct($myRule = '', RP_RHostAddr $RHostAddr_PI = null,
                                RP_KeyValPair $KeyValPair_PI = null, $outParamDesc = '',
                                RP_MsParamArray $MsParamArray_PI = null)
    {
        if (!isset($RHostAddr_PI)) $RHostAddr_PI = new RP_RHostAddr();
        if (!isset($KeyValPair_PI)) $KeyValPair_PI = new RP_KeyValPair();
        if (!isset($MsParamArray_PI)) $MsParamArray_PI = new RP_MsParamArray();

        $packlets = array("myRule" => $myRule, "RHostAddr_PI" => $RHostAddr_PI,
            "KeyValPair_PI" => $KeyValPair_PI, "outParamDesc" => $outParamDesc,
            "MsParamArray_PI" => $MsParamArray_PI);
        parent::__construct("ExecMyRuleInp_PI", $packlets);
    }

}
