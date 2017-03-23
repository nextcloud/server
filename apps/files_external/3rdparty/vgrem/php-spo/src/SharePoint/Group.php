<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ResourcePathEntity;


/**
 * Group resource
 */
class Group extends Principal
{

    /**
     * Gets a collection of user objects that represents all of the users in the group.
     * @return UserCollection
     */
    public function getUsers()
    {
        if(!$this->isPropertyAvailable('Users')){
            $this->setProperty("Users", new UserCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath() , "Users")));
        }
        return $this->getProperty("Users");
    }
}