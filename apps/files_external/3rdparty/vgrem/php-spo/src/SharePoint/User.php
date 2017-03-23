<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionUpdateEntity;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

/**
 * Represents a user in Microsoft SharePoint Foundation. A user is a type of SP.Principal.
 */
class User  extends Principal
{
    public function update()
    {
        $qry = new ClientActionUpdateEntity($this);
        $this->getContext()->addQuery($qry,$this);
    }

    /**
     * Gets the collection of groups of which the user is a member.
     * @return GroupCollection
     */
    public function getGroups()
    {
        if(!$this->isPropertyAvailable('Groups')){
            $this->setProperty("Groups", new GroupCollection($this->getContext(), new ResourcePathEntity($this->getContext(),$this->getResourcePath(), "groups")));
        }
        return $this->getProperty("Groups");
    }

}