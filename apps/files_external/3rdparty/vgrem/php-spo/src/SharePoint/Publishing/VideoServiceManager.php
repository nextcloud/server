<?php


namespace Office365\PHP\Client\SharePoint\Publishing;


use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ClientRuntimeContext;
use Office365\PHP\Client\Runtime\ResourcePathEntity;


class VideoServiceManager extends ClientObject
{
    public function __construct(ClientRuntimeContext $ctx,$videoPortalUrl)
    {
        $ctx->setServiceRootUrl($videoPortalUrl . "/_api/");
        parent::__construct($ctx,new ResourcePathEntity($ctx,null,"VideoService"));
    }


    /**
     * @return VideoChannelCollection
     */
    public function getChannels(){
        return new VideoChannelCollection(
            $this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"Channels")
        );
    }


    /**
     * @return VideoChannelCollection
     */
    public function getCanEditChannels(){
        return new VideoChannelCollection(
            $this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"CanEditChannels")
        );
    }



    /**
     * @return SpotlightChannelCollection
     */
    public function getSpotlightChannels(){
        return new SpotlightChannelCollection(
            $this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"SpotlightChannels")
        );
    }

    /**
     * @return SpotlightVideoCollection
     */
    public function getSpotlightVideos(){
        return new SpotlightVideoCollection(
            $this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"SpotlightVideos")
        );
    }

}