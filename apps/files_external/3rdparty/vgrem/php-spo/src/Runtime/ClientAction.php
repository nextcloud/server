<?php

namespace Office365\PHP\Client\Runtime;
use Office365\PHP\Client\Runtime\OData\ODataPayload;


/**
 * OData query class
 */
class ClientAction
{
    /**
     * @var int
     */
    public $ActionType;

    /**
     * @var string
     */
    public $ResourceUrl;

    /**
     * @var ODataPayload
     */
    public $Payload;


    /**
     * @var int
     */
    public $PayloadFormatType;


    /**
     * ClientAction constructor.
     * @param string $resourceUrl
     * @param ODataPayload $payload
     * @param int $actionType
     */
    public function __construct($resourceUrl, $payload=null, $actionType=null)
    {
        $this->ResourceUrl = $resourceUrl;
        $this->Payload = $payload;
        $this->ActionType = $actionType;
        $this->PayloadFormatType = FormatType::Json;
    }

    /**
     * @return string
     */
    public function getId(){
        return spl_object_hash($this);
    }

}

