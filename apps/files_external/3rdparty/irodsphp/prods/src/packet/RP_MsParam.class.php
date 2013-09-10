<?php

require_once(dirname(__FILE__) . "/../autoload.inc.php");
class RP_MsParam extends RODSPacket
{
    public function __construct($label = '', RODSPacket $inOutStruct = null,
                                RP_BinBytesBuf $BinBytesBuf_PI = null)
    {
        if (!isset($BinBytesBuf_PI)) $BinBytesBuf_PI = new RP_BinBytesBuf();
        if (!isset($inOutStruct)) $inOutStruct = new RODSPacket();

        $packlets = array("label" => $label, "type" => $inOutStruct->type,
            $inOutStruct->type => $inOutStruct, "BinBytesBuf_PI" => $BinBytesBuf_PI);
        parent::__construct("MsParam_PI", $packlets);
    }

    // need to overwrite it's parent function here, since $inOutStruct->type
    // can be undefined, when it's parent packet class was defined.
    public function fromSXE(SimpleXMLElement $sxe)
    {
        if (!isset($this->packlets))
            return;

        $this->packlets["label"] = (string)$sxe->label;
        $this->packlets["type"] = (string)$sxe->type;

        $typename = $this->packlets["type"]; //type of the expected packet
        if (substr($typename, -3, 3) != "_PI") {
            throw new RODSException("RP_MsParam::fromSXE " .
                    "The XML node's type is unexpected: '$typename' " .
                    " expecting some thing like xxx_PI",
                "SYS_PACK_INSTRUCT_FORMAT_ERR");
        }
        $rp_classname = "RP_" . substr($typename, 0, strlen($typename) - 3);
        $inOutStruct = new $rp_classname();
        $inOutStruct->fromSXE($sxe->$typename);
        $this->packlets["$typename"] = $inOutStruct;

        $this->packlets['BinBytesBuf_PI'] = new RP_BinBytesBuf();
        $this->packlets['BinBytesBuf_PI']->fromSXE($sxe->BinBytesBuf_PI);
    }

}
