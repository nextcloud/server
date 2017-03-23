<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

/**
 * Site resource
 */
class Site extends ClientObject
{
    /**
     * Returns the site with the specified GUID
     * @param string $webId
     * @return Web
     */
    public function openWebById($webId)
    {
        $web = new Web($this->getContext(),$this->getResourcePath());
        $qry = new ClientActionInvokePostMethod($this,"openWebById",array($webId));
        $this->getContext()->addQuery($qry,$web);
        return $web;
    }


    /**
     * @return Web
     */
    public function getRootWeb()
    {
        if(!$this->isPropertyAvailable("RootWeb")){
            $this->setProperty("RootWeb", new Web($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"RootWeb")));
        }
        return $this->getProperty("RootWeb");
    }


    /**
     * @return UserCustomActionCollection
     */
    public function getUserCustomActions()
    {
        if(!$this->isPropertyAvailable("UserCustomActions")){
            $this->setProperty("UserCustomActions", new UserCustomActionCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"UserCustomActions")));
        }
        return $this->getProperty("UserCustomActions");
    }
    
}