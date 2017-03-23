<?php


namespace Office365\PHP\Client\Discovery;


use Office365\PHP\Client\Runtime\ClientResult;

class CapabilityDiscoveryResult extends ClientResult
{

    /**
     * @var string $ServiceEndpointUri
     */
    public $ServiceEndpointUri;

    /**
     * @var string $ServiceResourceId
     */
    public $ServiceResourceId;

    /**
     * @var string $ServiceApiVersion
     */
    public $ServiceApiVersion;


    function convertFromJson($json)
    {
        parent::convertFromJson($json);
    }


    function convertToJson()
    {
        return parent::convertToJson();
    }

}