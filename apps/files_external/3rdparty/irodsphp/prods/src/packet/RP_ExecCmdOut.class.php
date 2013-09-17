<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RP_ExecCmdOut
 *
 * @author lisa
 */
require_once(dirname(__FILE__) . "/../autoload.inc.php");

class RP_ExecCmdOut extends RODSPacket
{

    public function __construct($buf = '', $buflen = 0)
    {
        $packlets = array("buf" => $buf);
        parent::__construct("ExecCmdOut_PI", $packlets);
    }

    public function fromSXE(SimpleXMLElement $sxe)
    {
        $binbytes = "BinBytesBuf_PI";
        $name = "buf";

        if (!isset($this->packlets))
            return;

        $packlet_value = "";
        try {
            foreach ($sxe->$binbytes as $binpacket) {
                if (strlen($binpacket->$name) > 0) {
                    $decoded_value = base64_decode($binpacket->$name);
                    $packlet_value .= $decoded_value;
                }
            }

            // can't find a better way yet to get rid of the garbage on the end of the string ...
            $len = strlen($packlet_value);
            $cleaned_value = "";
            for ($i = 0; $i < $len; $i++) {
                if (ord($packlet_value{$i}) <= 0) break;
                $cleaned_value .= $packlet_value{$i};
            }

            $this->packlets[$name] = $cleaned_value;
            $this->packlets["buflen"] = $i;
        } catch (Exception $ex) {
            $this->packlets[$name] = "";
        }
    }
}
