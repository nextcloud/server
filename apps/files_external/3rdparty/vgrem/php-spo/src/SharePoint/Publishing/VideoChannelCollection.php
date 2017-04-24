<?php


namespace Office365\PHP\Client\SharePoint\Publishing;


use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;

class VideoChannelCollection extends ClientObjectCollection
{

    /**
     * Create an video channel
     * @param string $title
     * @return VideoChannel
     */
    public function add($title) {
        $channel = new VideoChannel($this->getContext());
        $channel->setProperty("Title",$title);
        $channel->setProperty("TileHtmlColor","#0072c6");
        $qry = new ClientActionCreateEntity($this, $channel);
        $this->getContext()->addQuery($qry, $channel);
        $this->addChild($channel);
        return $channel;
    }

}