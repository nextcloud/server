<?php


namespace Office365\PHP\Client\Runtime;
use Office365\PHP\Client\Runtime\OData\ODataPathParser;

/**
 * Resource path to address Service Operations which represents simple functions exposed by an OData service
 */
class ResourcePathServiceOperation extends ResourcePath
{
    /**
     * ResourcePathMethod constructor.
     * @param ClientRuntimeContext $context
     * @param ResourcePath $parent
     * @param string $methodName
     * @param array $methodParameters
     */
    public function __construct(ClientRuntimeContext $context, ResourcePath $parent, $methodName, $methodParameters = null)
    {
        parent::__construct($context, $parent);
        $this->methodName = $methodName;
        $this->methodParameters = $methodParameters;
    }


    public function getName()
    {
        return ODataPathParser::fromMethod($this->methodName,$this->methodParameters);
    }

    /**
     * @var array
     */
    protected $methodParameters;

    /**
     * @var string
     */
    protected $methodName;

}