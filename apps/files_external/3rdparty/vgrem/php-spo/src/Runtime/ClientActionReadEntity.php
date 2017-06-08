<?php


namespace Office365\PHP\Client\Runtime;

class ClientActionReadEntity extends ClientAction
{
    /**
     * ClientActionUpdateMethod constructor.
     * @param string $resourceUrl
     */
    public function __construct($resourceUrl)
    {
        parent::__construct($resourceUrl,null,ClientActionType::GetMethod);
    }
}