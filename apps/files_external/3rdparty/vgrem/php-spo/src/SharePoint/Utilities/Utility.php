<?php

namespace Office365\PHP\Client\SharePoint\Utilities;

use Office365\PHP\Client\SharePoint\ContentType;
use Office365\PHP\Client\SharePoint\ListItem;
use Office365\PHP\Client\SharePoint\SPList;

/**
 * Class DiscussionBoard
 */
class Utility
{

    /**
     * Creates a discussion item in Discussion Board
     * @param SPList $list
     * @param string $title
     * @return ListItem
     */
    public static function createNewDiscussion(SPList $list, $title)
    {
        $discussionPayload = array(
            "Title" => $title,
            "FileSystemObjectType" => 1
        );
        $item = $list->addItem($discussionPayload);
        $item->getContext()->executeQuery();
        //fix discussion folder name
        $item->setProperty("FileLeafRef",$title);
        $item->update();
        $item->getContext()->executeQuery();
        return $item;
    }


    /**
     * Creates a message item (reply) in Discussion Board
     * @param ListItem $discussionItem
     * @param string $subject
     * @return ListItem
     * @throws \Exception
     */
    public static function createNewDiscussionReply(ListItem $discussionItem,$subject){
        $ctx = $discussionItem->getContext();
        $list = $discussionItem->getParentList();

        $contentTypes = $list->getContentTypes();
        $ctx->load($contentTypes);
        $ctx->executeQuery();
        $result = $contentTypes->findItems(
            function (ContentType $item){
              return  $item->getProperty("Name") === "Message";
        });
        if(count($result) == 0){
            throw new \Exception("Message content type not found");
        }

        $messagePayload = array(
            "Body" => $subject,
            "FileSystemObjectType" => 0,
            "ContentTypeId" => $result[0]->getProperty("Id")
        );
        $messageItem = $list->addItem($messagePayload);
        $ctx->executeQuery();
        //move message into discussion folder
        $ctx->load($discussionItem,array("FileRef"));
        $ctx->load($messageItem,array("FileRef","FileDirRef"));
        $ctx->executeQuery();
        $sourceFileUrl = $messageItem->getProperty("FileRef");
        $targetFileUrl = str_replace($messageItem->getProperty("FileDirRef"),$discussionItem->getProperty("FileRef"),$sourceFileUrl);
        $ctx->getWeb()->getFileByServerRelativeUrl($sourceFileUrl)->moveTo($targetFileUrl,1);
        $ctx->executeQuery();
        return $messageItem;
    }

}