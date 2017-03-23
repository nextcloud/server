<?php

namespace Office365\PHP\Client\SharePoint;

use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\OData\ODataPayload;
use Office365\PHP\Client\Runtime\ResourcePathServiceOperation;

class ViewCollection extends ClientObjectCollection
{
    /**
     * Get View by title
     * @param $title
     * @return View
     */
    public function getByTitle($title)
    {
        return new View(
            $this->getContext(),
            new ResourcePathServiceOperation($this->getContext(),$this->getResourcePath(),"getByTitle",array($title))
        );
    }


    /**
     * Get View by id
     * @param $id
     * @return View
     */
    public function getById($id)
    {
        return new View(
            $this->getContext(),
            new ResourcePathServiceOperation($this->getContext(),$this->getResourcePath(),"getById",array($id))
        );
    }




    /**
     * Creates a View resource
     * @param ViewCreationInformation $parameters
     * @return View
     */
    public function add(ViewCreationInformation $parameters)
    {
        $view = new View($this->getContext(),$this->getResourcePath());
        $qry = new ClientActionCreateEntity($this,$parameters);
        $this->getContext()->addQuery($qry,$view);
        $this->addChild($view);
        return $view;
    }
}