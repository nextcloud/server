<?php

require_once("autoload.inc.php");

$GLOBALS['RODSMessage_types'] = array(
    "RODS_CONNECT_T" => "RODS_CONNECT",
    "RODS_VERSION_T" => "RODS_VERSION",
    "RODS_API_REQ_T" => "RODS_API_REQ",
    "RODS_DISCONNECT_T" => "RODS_DISCONNECT",
    "RODS_REAUTH_T" => "RODS_REAUTH",
    "RODS_API_REPLY_T" => "RODS_API_REPLY"
);

class RODSMessage
{
    private $type; // (String) message type, such as "RODS_CONNECT_T"
    private $typestr; // (String) str representation of the type that RODS server understand
    private $msg; // (RODSPacket) main message body
    private $header; // (RODSPacket) a special packet, header for other packets
    private $header_xml; // (string) packet header in XML
    private $msg_xml; // (string) message in XML
    private $binstr; // (string) binary string
    private $errstr; // (string) error string
    private $intinfo; // an additional integer info, for API, it is the
    // apiReqNum
    private $serialized;

    public function __construct($type = NULL, $_msg = NULL, $intinfo = 0, $binstr = "", $errstr = "")
    {
        if (!isset($type)) {
            return;
        }

        $this->type = $type;
        $RODSMessage_types = $GLOBALS['RODSMessage_types'];
        if (!isset($RODSMessage_types[$type])) {
            throw new RODSException("RODSMessage::__construct failed.1! Unknown type '$type'",
                "PERR_INTERNAL_ERR");
        }
        $this->typestr = $RODSMessage_types[$type];

        if (isset($_msg)) {
            if (!($_msg instanceof RODSPacket)) {
                throw new RODSException("RODSMessage::__construct failed.2!",
                    "PERR_INTERNAL_ERR");
            }
        }
        $this->msg = $_msg;
        $this->intinfo = $intinfo;
        $this->binstr = $binstr;
        $this->errstr = $errstr;
    }

    public function pack()
    {
        if (isset($this->msg))
            $this->msg_xml = $this->msg->toXML();

        $this->header = new RP_MsgHeader($this->typestr, strlen($this->msg_xml),
            strlen($this->errstr), strlen($this->binstr), $this->intinfo);
        $header_xml = $this->header->toXML();
        $this->serialized = pack("N", strlen($header_xml)) . $header_xml .
            $this->msg_xml;
        return $this->serialized;
    }


    public function unpack($conn, &$bslen = NULL)
    {
        if (FALSE === ($chunk = stream_get_contents($conn, 4))) {
            throw new RODSException("RODSMessage::unpack failed.0! ",
                "SYS_PACK_INSTRUCT_FORMAT_ERR");
        }


        $arr = unpack("Nlen", $chunk);
        $header_len = $arr['len'];
        if ((!is_int($header_len)) || ($header_len < 1) || ($header_len > 8192 - 4)) {
            throw new RODSException("RODSMessage::unpack failed.1! The header length is unexpected: '$header_len'",
                "SYS_PACK_INSTRUCT_FORMAT_ERR");
        }

        $this->header_xml = stream_get_contents($conn, $header_len);
        $this->parseHeaderXML($this->header_xml);
        $intInfo = $this->header->intInfo;

        // get main msg string
        $msg_len = $this->header->msgLen;
        $this->msg_xml = stream_get_contents($conn, $msg_len);
        if ($msg_len != strlen($this->msg_xml)) {
            throw new RODSException("RODSMessage::unpack failed.2! " .
                    "The body length is unexpected: " . strlen($this->msg_xml) .
                    " expecting: $msg_len",
                "SYS_PACK_INSTRUCT_FORMAT_ERR");
        }
        if ($msg_len > 0) {
            $this->parseBodyXML($this->msg_xml);
        }

        // get err string
        $errlen = $this->header->errorLen;
        $this->errstr = stream_get_contents($conn, $errlen);
        if ($errlen != strlen($this->errstr)) {
            throw new RODSException("RODSMessage::unpack failed.3! " .
                    "The err length is unexpected: " . strlen($this->errstr) .
                    " expecting: $errlen",
                "SYS_PACK_INSTRUCT_FORMAT_ERR");
        }

        // get bin string
        $bslen = $this->header->bsLen;
        $this->binstr = stream_get_contents($conn, $bslen);
        if ($bslen != strlen($this->binstr)) {
            throw new RODSException("RODSMessage::unpack failed.4! " .
                    "The bin str length is unexpected: " . strlen($this->binstr) .
                    " expecting: $bslen",
                "SYS_PACK_INSTRUCT_FORMAT_ERR");
        }

        return $this->header->intInfo;
    }

    private function parseHeaderXML($xmlstr)
    {
        $xml = new SimpleXMLElement($xmlstr);
        $name = $xml->getName();
        if ($name != "MsgHeader_PI") {
            throw new RODSException("RODSMessage::parseHeaderXML failed! " .
                    "The XML header name is unexpected:$name " .
                    " expecting: MsgHeader_PI",
                "SYS_PACK_INSTRUCT_FORMAT_ERR");
        }
        $this->header = new RP_MsgHeader();
        $this->header->fromSXE($xml);
    }

    private function parseBodyXML($xmlstr)
    {
        //try {
        $xml = new SimpleXMLElement($xmlstr);
        $name = $xml->getName();
        if (substr($name, -3, 3) != "_PI") {
            throw new RODSException("RODSMessage::parseMainBodyXML failed! " .
                    "The XML node's name is unexpected:$name " .
                    " expecting some thing like xxx_PI",
                "SYS_PACK_INSTRUCT_FORMAT_ERR");
        }
        $rp_classname = "RP_" . substr($name, 0, strlen($name) - 3);
        $this->msg = new $rp_classname();
        $this->msg->fromSXE($xml);

        /*} catch (Exception $e) {
          throw new RODSException("RODSMessage::parseMainBodyXML failed! ".
              "Mal formated XML in RODS message :".
              $xmlstr,
              "SYS_PACK_INSTRUCT_FORMAT_ERR",$e);
        }
        */
    }

    public function getBody()
    {
        return $this->msg;
    }

    public function getBinstr()
    {
        return $this->binstr;
    }

    public function getXML()
    {
        return $this->header_xml . "\n" . $this->msg_xml;
    }

    public static function packConnectMsg($user, $zone, $relVersion = RODS_REL_VERSION,
                                          $apiVersion = RODS_API_VERSION, $option = NULL)
    {
        $msgbody = new RP_StartupPack($user, $zone, $relVersion, $apiVersion . $option);
        $rods_msg = new RODSMessage("RODS_CONNECT_T", $msgbody);
        return $rods_msg->pack();
    }
}
