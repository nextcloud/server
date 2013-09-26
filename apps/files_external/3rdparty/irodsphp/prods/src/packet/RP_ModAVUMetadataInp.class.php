<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_ModAVUMetadataInp extends RODSPacket
{
    public function __construct($arg0 = NULL, $arg1 = NULL, $arg2 = NULL,
                                $arg3 = NULL, $arg4 = NULL, $arg5 = NULL, $arg6 = NULL, $arg7 = NULL, $arg8 = NULL,
                                $arg9 = NULL)
    {
        $packlets = array("arg0" => $arg0, "arg1" => $arg1, "arg2" => $arg2,
            "arg3" => $arg3, "arg4" => $arg4, "arg5" => $arg5,
            "arg6" => $arg6, "arg7" => $arg7, "arg8" => $arg8, "arg9" => $arg9);
        parent::__construct("ModAVUMetadataInp_PI", $packlets);
    }

}
