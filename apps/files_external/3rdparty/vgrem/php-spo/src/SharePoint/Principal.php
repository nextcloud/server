<?php


namespace Office365\PHP\Client\SharePoint;

use Office365\PHP\Client\Runtime\ClientObject;

class Principal extends ClientObject
{

    /**
     * @return PrincipalType
     */
    public function getPrincipalType()
    {
        if($this->isPropertyAvailable('PrincipalType')){
            return $this->getProperty("PrincipalType");
        }
        return null;
    }

}