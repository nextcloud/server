<?php


namespace Office365\PHP\Client\Runtime;


class ClientActionUpdateEntity extends ClientAction
{

    /**
     * ClientActionUpdateEntity constructor.
     * @param ClientObject $clientObject
     */
    public function __construct(ClientObject $clientObject)
    {
        parent::__construct($clientObject->getResourceUrl(), $clientObject, (int)ClientActionType::UpdateEntity);
    }
}