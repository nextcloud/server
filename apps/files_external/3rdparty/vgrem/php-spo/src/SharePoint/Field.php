<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientActionUpdateEntity;
use Office365\PHP\Client\Runtime\ClientObject;

/**
 * Represents a field in a SharePoint list.
 */
class Field extends ClientObject
{

    public function update()
    {
        $qry = new ClientActionUpdateEntity($this);
        $this->getContext()->addQuery($qry,$this);
    }

    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
    }

    /**
     * Sets the value of the ShowInDisplayForm property for this field.
     * @param $value true to show the field in the form; otherwise false.
     */
    public function setShowInDisplayForm($value){
        $qry = new ClientActionInvokePostMethod(
            $this,
            "setShowInDisplayForm",
            array($value)
        );
        $this->getContext()->addQuery($qry);
    }
}