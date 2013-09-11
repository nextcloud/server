<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_GenQueryOut extends RODSPacket
{
    // if one of the packlet is an array of packets, define it here.
    protected $array_rp_type;

    public function __construct($rowCnt = 0, $attriCnt = 0, $continueInx = 0,
                                $totalRowCount = 0, array $SqlResult_PI = array())
    {
        $this->array_rp_type = array("SqlResult_PI" => "RP_SqlResult");

        $packlets = array("rowCnt" => $rowCnt, 'attriCnt' => $attriCnt,
            'continueInx' => $continueInx, 'totalRowCount' => $totalRowCount,
            'SqlResult_PI' => $SqlResult_PI);
        parent::__construct("GenQueryOut_PI", $packlets);
    }

}
