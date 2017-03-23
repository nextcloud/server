<?php


namespace Office365\PHP\Client\OutlookServices;


use Office365\PHP\Client\Runtime\ResourcePathEntity;

class MailFolder extends OutlookEntity
{
    /**
     * The number of folders in the folder.
     * @var int
     */
    public $ChildFolderCount;

    /**
     * @return MessageCollection
     */
    public function getMessages()
    {
        if (!$this->isPropertyAvailable("Messages")) {
            $this->setProperty("Messages",
                new MessageCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Messages"
                )));
        }
        return $this->getProperty("Messages");
    }

    /**
     * @return FolderCollection
     */
    public function getChildFolders()
    {
        if (!$this->isPropertyAvailable("ChildFolders")) {
            $this->setProperty("ChildFolders",
                new FolderCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "ChildFolders"
                )));
        }

        return $this->getProperty("ChildFolders");
    }
}