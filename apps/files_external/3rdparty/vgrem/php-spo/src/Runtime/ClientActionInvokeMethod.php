<?php


namespace Office365\PHP\Client\Runtime;


use Office365\PHP\Client\Runtime\OData\ODataPathParser;
use Office365\PHP\Client\Runtime\OData\ODataPayload;

abstract class ClientActionInvokeMethod extends ClientAction
{


    /**
     * ClientActionInvokeMethod constructor.
     * @param ClientObject $parentClientObject
     * @param array $methodName
     * @param array $actionParameters
     * @param mixed $requestPayload
     * @param int $actionType
     */
    public function __construct(ClientObject $parentClientObject, $methodName=null, array $actionParameters=null, $requestPayload = null, $actionType = ClientActionType::GetMethod)
    {
        $url = $parentClientObject->getResourceUrl() . "/" . ODataPathParser::fromMethod($methodName,$actionParameters);
        parent::__construct($url,$requestPayload,$actionType);
        $this->MethodName = $methodName;
        $this->MethodParameters = $actionParameters;
    }


    /**
     * @var string
     */
    public $MethodName;


    /**
     * @var array
     */
    public $MethodParameters;
}