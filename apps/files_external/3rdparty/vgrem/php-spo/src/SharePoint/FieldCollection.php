<?php
/**
 * Represents a collection of Field resources
 */

namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\OData\ODataPayload;
use Office365\PHP\Client\Runtime\ResourcePathServiceOperation;

class FieldCollection extends ClientObjectCollection
{

    /**
     * Creates a Field resource
     * @param FieldCreationInformation $parameters
     * @return Field
     */
    public function add(FieldCreationInformation $parameters)
    {
        $field = new Field($this->getContext(),$this->getResourcePath());
        $qry = new ClientActionCreateEntity($this,$parameters);
        $this->getContext()->addQuery($qry,$field);
        $this->addChild($field);
        return $field;
    }


    /**
     * @param string $title
     * @return Field
     */
    public function getByTitle($title)
    {
        return new Field(
            $this->getContext(),
            new ResourcePathServiceOperation($this->getContext(),$this->getResourcePath(),"getByTitle",array($title))
        );
    }

    /**
     * @param string $internalNameOrTitle
     * @return Field
     */
    public function getByInternalNameOrTitle($internalNameOrTitle)
    {
        return new Field(
            $this->getContext(),
            new ResourcePathServiceOperation($this->getContext(),$this->getResourcePath(),"getByInternalNameOrTitle",array($internalNameOrTitle))
        );
    }
}