<?php
/**
 * Defines the securable object role assignments for a user or group on the Web site, list, or list item.
 */

namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientActionUpdateEntity;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

class RoleAssignment extends ClientObject
{

    /**
     * Updates Role Assignment
     */
    public function update()
    {
        $qry = new ClientActionUpdateEntity($this);
        $this->getContext()->addQuery($qry,$this);
    }


    /**
     * Deletes Role Assignment
     */
    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
    }



    /**
     * @return Principal
     */
    public function getMember()
    {
        if(!$this->isPropertyAvailable("Member")){
            $this->setProperty("Member", new Principal($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"Member")));
        }
        return $this->getProperty("Member");
    }

    function getProperty($name)
    {
        if($name == "Member" && !$this->isPropertyAvailable("Member"))
            return $this->getMember();
        return parent::getProperty($name);
    }


}