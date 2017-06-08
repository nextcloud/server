<?php


namespace Office365\PHP\Client\SharePoint\Publishing;


use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

class VideoChannel extends ClientObject
{

    public function getId(){
        return $this->getProperty("Id");
    }

    public function getTitle(){
        return $this->getProperty("Title");
    }

    public function getDescription(){
        return $this->getProperty("Description");
    }

    public function getServerRelativeUrl(){
        return $this->getProperty("ServerRelativeUrl");
    }


    public function getTileHtmlColor(){
        return $this->getProperty("TileHtmlColor");
    }


    /**
     * @return VideoCollection
     */
    public function GetAllVideos(){
        return new VideoCollection(
            $this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"GetAllVideos")
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


    /**
     * @return VideoCollection
     */
    public function getVideos(){
        return new VideoCollection(
            $this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"Videos")
        );
    }


    public function getEntityTypeName()
    {
        return implode(".",array("SP","Publishing",parent::getEntityTypeName()));
    }

}