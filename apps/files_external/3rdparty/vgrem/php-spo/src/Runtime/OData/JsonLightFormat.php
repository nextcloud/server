<?php


namespace Office365\PHP\Client\Runtime\OData;


class JsonLightFormat extends ODataFormat
{

     public function __construct($metadataLevel)
     {
         parent::__construct($metadataLevel);
     }


    public function getMediaType()
    {
        return "application/json; OData={$this->MetadataLevel}";
    }

}