<?php


namespace Office365\PHP\Client\Runtime;

/**
 * Resource path  for addressing a Collection (of entities), a single entity within a Collection,
 * as well as a property of an Entry
 */
class ResourcePathEntity extends ResourcePath
{

    /**
     * ResourcePathEntry constructor.
     * @param ClientRuntimeContext $context
     * @param ResourcePath $parent
     * @param $entityName
     */
    public function __construct(ClientRuntimeContext $context, ResourcePath $parent = null, $entityName)
    {
        parent::__construct($context, $parent);
        $this->entityName = $entityName;
    }


    /**
     * Gets entity name
     * @return string
     */
    public function getName()
    {
        return $this->entityName;
    }
    

    /**
     * @var string
     */
    protected $entityName;
    
}