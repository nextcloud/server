<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_TransStat extends RODSPacket
{
    public function __construct($numThreads = 0, $bytesWritten = 0)
    {
        $packlets = array("numThreads" => $numThreads,
            'bytesWritten' => $bytesWritten);
        parent::__construct("TransStat_PI", $packlets);
    }

}
