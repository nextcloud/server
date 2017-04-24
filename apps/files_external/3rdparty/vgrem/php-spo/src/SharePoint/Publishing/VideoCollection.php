<?php


namespace Office365\PHP\Client\SharePoint\Publishing;


use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;

class VideoCollection extends ClientObjectCollection
{

    public function add($title,$description=null,$fileName=null)
    {
        $videoItem = new VideoItem($this->getContext());
        $videoItem->setProperty("Title",$title);
        if(isset($description))
            $videoItem->setProperty("Description",$description);
        if(isset($fileName))
            $videoItem->setProperty("FileName",$fileName);
        $qry = new ClientActionCreateEntity($this, $videoItem);
        $this->getContext()->addQuery($qry, $videoItem);
        $this->addChild($videoItem);
        return $videoItem;
    }


    /**
     * @return string
     */
    public function getItemTypeName()
    {
        return __NAMESPACE__ . "\\" . "VideoItem";
    }
}