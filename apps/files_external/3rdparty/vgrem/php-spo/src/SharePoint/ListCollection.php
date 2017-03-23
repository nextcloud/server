<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\OData\ODataPayload;
use Office365\PHP\Client\Runtime\ResourcePathServiceOperation;


/**
 * List collection
 */
class ListCollection extends ClientObjectCollection
{
    /**
     * Get List by title
     * @param $title
     * @return SPList
     */
    public function getByTitle($title)
    {
        return new SPList(
            $this->getContext(),
            new ResourcePathServiceOperation($this->getContext(),$this->getResourcePath(),"getByTitle",array($title))
        );
    }

    /**
     * Get List by id
     * @param $id
     * @return SPList
     */
    public function getById($id)
    {
        return new SPList(
            $this->getContext(),
            new ResourcePathServiceOperation($this->getContext(),$this->getResourcePath(),"getById",array($id))
        );
    }


    /**
     * Creates a List resource
     * @param ListCreationInformation $parameters
     * @return SPList
     */
    public function add(ListCreationInformation $parameters)
    {
        $list = new SPList($this->getContext());
        $qry = new ClientActionCreateEntity($this,$parameters);
        $this->getContext()->addQuery($qry,$list);
        $this->addChild($list);
        return $list;
    }

    /**
     * @return string
     */
    public function getItemTypeName()
    {
        return __NAMESPACE__ . "\\" . "SPList";
    }
}