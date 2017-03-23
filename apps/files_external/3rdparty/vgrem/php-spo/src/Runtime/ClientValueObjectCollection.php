<?php

namespace Office365\PHP\Client\Runtime;


use Office365\PHP\Client\Runtime\OData\ODataPrimitiveTypeKind;

class ClientValueObjectCollection extends ClientValueObject
{

    /**
     * @param ClientValueObject $value
     */
    public function addChild(ClientValueObject $value)
    {
        if (is_null($this->data))
            $this->data = array();
        $this->data[] = $value;
    }


    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * Clear collection
     */
    public function clearData()
    {
        $this->data = array();
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->data);
    }


    /**
     * Creates entity for a collection
     * @return ClientValueObject
     */
    public function createTypedValueObject()
    {
        $clientValueObjectType = $this->getItemTypeName();
        return new $clientValueObjectType();
    }

    /**
     * @return string
     */
    function getItemTypeName()
    {
        return str_replace("Collection","",get_class($this));
    }


    function convertFromJson($json)
    {
        $this->clearData();
        if (in_array($this->getEntityTypeName(), ODataPrimitiveTypeKind::getPrimitiveCollectionNames())) {
            $this->data = $json->results;
        } else {
            foreach ($json as $item) {
                $clientValueObject = $this->createTypedValueObject();
                $clientValueObject->convertFromJson($item);
                $this->addChild($clientValueObject);
            }
        }
    }


    /**
     * @var array
     */
    private $data = null;

    

}