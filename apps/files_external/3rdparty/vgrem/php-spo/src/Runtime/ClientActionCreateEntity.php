<?php
namespace Office365\PHP\Client\Runtime;

use Office365\PHP\Client\Runtime\OData\ODataPayload;

class ClientActionCreateEntity extends ClientAction
{

    /**
     * ClientActionUpdateEntity constructor.
     * @param ClientObject $entityCollection
     * @param ODataPayload $payload
     */
    public function __construct(ClientObject $entityCollection, ODataPayload $payload = null)
    {
        parent::__construct($entityCollection->getResourceUrl(), $payload, (int)ClientActionType::CreateEntity);
    }

}