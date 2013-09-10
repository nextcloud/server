<?php

//require_once(dirname(__FILE__)."/../autoload.inc.php");

/*
$GLOBALS['Pack_PI_Table']=array (
  "MsgHeader_PI" => array ("type" => NULL, "msgLen" => 0, 
      "errorLen" => 0, "bsLen" => 0, "intInfo" => 0),
  "StartupPack_PI" => array ("irodsProt" => 1, "connectCnt" => 0, 
      "proxyUser" => NULL, "proxyRcatZone" => NULL, "clientUser" => NULL,  
      "clientRcatZone" => NULL, "relVersion" => NULL,  
      "apiVersion" => NULL, "option" => NULL ),
   "Version_PI" => array ("status"=>0,"relVersion"=>NULL,"apiVersion"=>NULL),
   "authResponseInp_PI" => array("response" => NULL, "username" => NULL),
   "authRequestOut_PI"  => array("challenge" => NULL)   
);
*/

class RODSPacket
{
    protected $type; // type of packet
    protected $packlets; // (array of mixed) main message body

    public function __construct($type = NULL, array $arr = NULL)
    {
        if (!isset($type))
            return;

        $this->type = $type;
        $this->packlets = $arr;
    }

    public function toXML()
    {
        if (empty($this->type))
            return NULL;

        $doc = new DOMDocument();
        $root = $this->toDOMElement($doc);
        $doc->appendChild($root);
        return ($doc->saveXML($root, LIBXML_NOEMPTYTAG));
    }

    /*
    public function fromXML($str)
    {
      try {
        $xml = new SimpleXMLElement($str);
      } catch (Exception $e) {
        throw new RODSException("RODSPacket::fromXML failed. ".
            "Mal-formated XML: '$str'\n",
            PERR_INTERNAL_ERR);
      }

      if (isset($this->type)&&($this->type!=$xml->getName()))
      {
        throw new RODSException("RODSPacket::fromXML failed. ".
            "Possible type mismatch! expected type:".$this->type." but got: ".
            $xml->getName()." \n",
            PERR_INTERNAL_ERR);
      }

      $this->type=$xml->getName();

      foreach($xml as $key => $val)
      {
        if (!array_key_exists($key,$this->msg))
        {
          throw new RODSException("RODSPacket::fromXML failed. ".
            "Possible type mismatch! expected key '$key' doesn't exists\n",
            PERR_INTERNAL_ERR);
        }
        $this->msg[$key]=(string)$val;
      }
    }
    */

    public static function parseXML($xmlstr)
    {
        if (false == ($doc = DOMDocument::loadXML($xmlstr))) {
            throw new RODSException("RODSPacket::parseXML failed. " .
                    "Failed to loadXML(). The xmlstr is: $xmlstr\n",
                PERR_UNEXPECTED_PACKET_FORMAT);
        }

        $rp_classname = "RP_" . substr($doc->tagName, 0, strlen($doc->tagName) - 3);
        $packet = new $rp_classname();
        $packet->fromDOM($doc);
    }

    /*
    public function fromDOM(DOMNode $domnode)
    {
      if (!isset($this->packlets))
        return;

      $i=0;
      $domnode_children=$domnode->childNodes;

      foreach($this->packlets as $packlet_key => &$packlet_val)
      {
        $domnode_child=$domnode_children->item($i++);

        // check if the tag names are expected
        if ($domnode_child->tagName!=$packlet_key)
        {
          throw new RODSException("RODSPacket::fromDOM failed. ".
            "Expecting packlet:$packlet_key, but got:".$domnode_child->tagName." \n",
            PERR_UNEXPECTED_PACKET_FORMAT);
        }

        if (is_a($packlet_val, "RODSPacket")) //if expecting sub packet
        {
          $packlet_val->fromDOM($domnode_child);
        }
        else //if expecting an string
        {

        }
      }
    }

    */

    public function fromSXE(SimpleXMLElement $sxe)
    {
        if (!isset($this->packlets))
            return;

        foreach ($this->packlets as $packlet_key => &$packlet_val) {
            if ($packlet_val instanceof RODSPacket) //if expecting sub packet
            {
                if (!isset($sxe->$packlet_key)) {
                    throw new RODSException("RODSPacket(" . get_class($this) . ")::fromSXE failed. " .
                            "Failed to find expected packlet: '$packlet_key' \n",
                        "PERR_UNEXPECTED_PACKET_FORMAT");
                }
                $packlet_val->fromSXE($sxe->$packlet_key);
            } else
                if (is_array($packlet_val)) //if expecting array
                {
                    if (isset($sxe->$packlet_key)) {
                        $packlet_val = array();
                        foreach ($sxe->$packlet_key as $sxe_val) {
                            if ((!empty($this->array_rp_type)) &&
                                (!empty($this->array_rp_type["$packlet_key"]))
                            ) // if it's an array of packets
                            {
                                $class_name = $this->array_rp_type[$packlet_key];
                                $sub_array_packet = new $class_name();
                                $sub_array_packet->fromSXE($sxe_val);
                                $packlet_val[] = $sub_array_packet;
                            } else {
                                $packlet_val[] = (string)$sxe_val;
                            }
                        }
                    }

                } else {
                    if (isset($sxe->$packlet_key)) {
                        $packlet_val = (string)$sxe->$packlet_key;
                    }
                }
        }
        /*
        foreach($sxe->children() as $child)
        {
          $tagname=$child->getName();
          if(substr($tagname,-3,3)=="_PI")
          {
            $rp_classname="RP_".substr($name,0,strlen($name)-3);
            $child_rp=new $rp_classname();
            $child_rp->fromSXE($child);
          }
          else
          {
            $this->packlets[$child->getName()]=(string)$child;
          }
        }
        */
    }

    public function toDOMElement(DOMDocument $doc)
    {
        if (empty($this->type))
            return NULL;

        $node = $doc->createElement($this->type);

        foreach ($this->packlets as $name => $packlet) {
            if ($packlet instanceof RODSPacket) //if node is a packet
            {
                $child_node = $packlet->toDOMElement($doc);
                if (isset($child_node))
                    $node->appendChild($packlet->toDOMElement($doc));
            } else
                if (is_array($packlet)) //if node is an array
                {
                    if (isset($packlet)) {
                        foreach ($packlet as $sub_packlet) {
                            if ($sub_packlet instanceof RODSPacket) //if sub_node is a packet
                            {
                                $child_node = $sub_packlet->toDOMElement($doc);
                                if (isset($child_node))
                                    $node->appendChild($sub_packlet->toDOMElement($doc));
                            } else {
                                //echo "sub_packlet = $sub_packlet<br/>\n";
                                $node->appendChild($doc->createElement($name, htmlspecialchars($sub_packlet)));
                            }
                        }
                    }
                } else //if node holds a string
                { //echo "packlet = $packlet<br/>\n";
                    $node->appendChild($doc->createElement($name, htmlspecialchars($packlet)));
                }
        }

        return $node;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->packlets))
            return $this->packlets[$name];
        else {
            debug_print_backtrace();
            throw new RODSException("RODSPacket::__get() failed. Trying to access field '$name' that doesn't exist!",
                "PERR_INTERNAL_ERR");
        }
    }

    public function __set($name, $val)
    {
        if (array_key_exists($name, $this->packlets))
            $this->packlets[$name] = $val;
        else
            throw new RODSException("RODSPacket::__set() failed. Trying to access field '$name' that doesn't exist!",
                "PERR_INTERNAL_ERR");
    }

    /*
    public static function makeStartupPack($user,$zone)
    {
      $msg=array(1,0,$user,$zone,$user,$zone,'rods0.5','a',NULL);
      return (new RODSPacket("StartupPack_PI",$msg));
    }
    */
}
