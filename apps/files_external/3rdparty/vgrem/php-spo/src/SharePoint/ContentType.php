<?php

namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientObject;

class ContentType extends ClientObject
{

    /**
     * Deletes content Type resource
     */
    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
    }



    function setProperty($name, $value, $persistChanges = true)
    {
        if ($name == "StringId") {
            $this->setResourceUrl($this->parentCollection->getResourcePath()->toUrl() . "('{$value}')");
            $this->{$name} = $value;
        }
        elseif ($name == "Id"){
            $this->{$name} = $value->StringValue;
        }
        else
            parent::setProperty($name, $value, $persistChanges);
    }


}