<?php

namespace Office365\PHP\Client\Runtime;
use Office365\PHP\Client\Runtime\OData\ODataPayload;

class ClientActionInvokePostMethod extends ClientActionInvokeMethod
{

    /**
     * ClientActionUpdateMethod constructor.
     * @param ClientObject $parentClientObject
     * @param string $methodName
     * @param array $actionParameters
     * @param ODataPayload|string $requestPayload
     */
    public function __construct(ClientObject $parentClientObject, $methodName = null, array $actionParameters = null, $requestPayload=null)
    {
        parent::__construct($parentClientObject,$methodName,$actionParameters,$requestPayload,ClientActionType::PostMethod);
    }
    

}