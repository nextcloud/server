<?php


namespace Office365\PHP\Client\Runtime;
use Office365\PHP\Client\Runtime\OData\ODataPayload;



/**
 * Represents OData complex type(property) of a server-side property value.
 */
class ClientValueObject extends ODataPayload
{

    /**
     * ClientValueObject constructor.
     * @param string $entityTypeName
     */
    public function __construct($entityTypeName = null)
    {
        $this->entityTypeName = $entityTypeName;
    }

    public function getEntityTypeName()
    {
        if(!isset($this->entityTypeName)){
            $typeInfo = explode("\\",get_class($this));
            $this->entityTypeName =  end($typeInfo);
        }
        return $this->entityTypeName;
    }

    /**
     * CConverts the JSON into a complex type
     * @param mixed $json
     */
    function convertFromJson($json)
    {
        foreach ($json as $key => $value) {
            if ($this->isMetadataProperty($key)) {
                continue;
            }
            if (is_object($value)) {
                if ($this->isDeferredProperty($value)) { //deferred property
                    $this->{$key} = null;
                }
                else {
                    if(property_exists($value,"results")) //collection of properties?
                        $this->{$key} = $value->results;
                }
            }
            else {
                $this->{$key} = $value;
            }
        }
    }



    /**
     * @var string
     */
    private $entityTypeName;


}