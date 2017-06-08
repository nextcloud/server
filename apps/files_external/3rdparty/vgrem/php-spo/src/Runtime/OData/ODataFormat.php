<?php


namespace Office365\PHP\Client\Runtime\OData;

abstract class ODataFormat
{


    public function __construct($metadataLevel)
    {
        $this->MetadataLevel = $metadataLevel;
    }


    /**
     * @return string
     */
    public abstract function getMediaType();

    /**
     * @return bool
     */
    public function isJson()
    {
       if($this instanceof JsonFormat || $this instanceof JsonLightFormat)
           return true;
        return false;
    }


    /**
     * @return bool
     */
    public function isAtom()
    {
        if($this instanceof AtomFormat)
            return true;
        return false;
    }

    /**
     * Controls information from the payload
     * @var int
     */
    public $MetadataLevel;

}