<?php
namespace Office365\PHP\Client\SharePoint\Publishing;

use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;
use Office365\PHP\Client\SharePoint\ClientContext;

/**
 * Class VideoServiceDiscoverer
 */
class VideoServiceDiscoverer extends ClientObject
{
    public function __construct(ClientContext $ctx)
    {
        parent::__construct($ctx,new ResourcePathEntity($ctx,null,"VideoService.Discover"));
    }



    public function getVideoPortalUrl(){
        return $this->getProperty("VideoPortalUrl");
    }



    public function getVideoPortalLayoutsUrl(){
        return $this->getProperty("VideoPortalLayoutsUrl");
    }


    public function getPlayerUrlTemplate(){
        return $this->getProperty("PlayerUrlTemplate");
    }

    /**
     * @return boolean
     */
    public function getIsVideoPortalEnabled(){
        return $this->getProperty("IsVideoPortalEnabled");
    }

}