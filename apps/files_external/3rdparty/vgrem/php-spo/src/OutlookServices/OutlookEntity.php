<?php


namespace Office365\PHP\Client\OutlookServices;


use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientActionUpdateEntity;
use Office365\PHP\Client\Runtime\ClientObject;
use ReflectionObject;
use ReflectionProperty;

class OutlookEntity extends ClientObject
{



    /**
     * Updates a resource
     */
    public function update()
    {
        $qry = new ClientActionUpdateEntity($this);
        $this->getContext()->addQuery($qry);
    }


    /**
     * Deletes a resource
     */
    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
    }


    public function addAnnotation($name, $value)
    {
        $this->annotations["@odata.$name"] = $value;
    }

    public function ensureTypeAnnotation()
    {
        $typeName = $this->getEntityTypeName();
        $this->addAnnotation("type","#Microsoft.OutlookServices.$typeName");
    }


    function getChangedProperties()
    {
        $properties = parent::getChangedProperties();
        $reflection = new ReflectionObject($this);
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $p) {
            $val = $p->getValue($this);
            if (!is_null($val)) {
                $properties[$p->name] = $val;
            }
        }
        foreach ($this->annotations as $key => $val) {
            $properties[$key] = $val;
        }
        return $properties;
    }


    function setProperty($name, $value, $persistChanges = true)
    {
        if($name == "Id"){
            if(is_null($this->getResourcePath()))
                $this->setResourceUrl($this->parentCollection->getResourcePath()->toUrl() . "/" . $value);
            $this->{$name} = $value;
        }
        else
            parent::setProperty($name, $value, $persistChanges);
    }



    /**
     * @var string
     */
    public $Id;


    /**
     * @var array
     */
    protected $annotations = array();

}