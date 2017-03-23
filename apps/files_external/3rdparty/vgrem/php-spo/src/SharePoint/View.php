<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientActionInvokeGetMethod;
use Office365\PHP\Client\Runtime\ClientActionUpdateEntity;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

/**
 * Specifies a list view.
 */
class View extends ClientObject
{

    /**
     * Updates view resource
     */
    public function update()
    {
        $qry = new ClientActionUpdateEntity($this);
        $this->getContext()->addQuery($qry,$this);
    }

    /**
     * Gets a value that specifies the collection of fields in the list view.
     * @return ViewFieldCollection
     */
    public function getViewFields()
    {
        if(!$this->isPropertyAvailable('ViewFields')){
            $this->setProperty("ViewFields", new ViewFieldCollection($this->getContext(), new ResourcePathEntity($this->getContext(),$this->getResourcePath(), "ViewFields")));
        }
        return $this->getProperty("ViewFields");
    }


    /**
     * The recommended way to delete a view is to send a DELETE request to the View resource endpoint,
     * as shown in View request examples.
     */
    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
    }


    /**
     * Returns the list view as HTML.
     */
    public function renderAsHtml(){
        $qry = new ClientActionInvokeGetMethod(
            $this,
            "renderashtml"
        );
        $this->getContext()->addQuery($qry);
    }

}