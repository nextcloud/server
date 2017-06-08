<?php


namespace Office365\PHP\Client\OutlookServices;

use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;

class FolderCollection extends ClientObjectCollection
{

    /**
     * Creates a folder
     * @param string $displayName name of new folder
     * @return MailFolder
     */
    public function createFolder($displayName) {
        $folder = new MailFolder($this->getContext(), $this->getResourcePath());
        $folder->setProperty('DisplayName', $displayName);
        $qry = new ClientActionCreateEntity($this, $folder);
        $this->getContext()->addQuery($qry, $folder);
        $this->addChild($folder);
        return $folder;
    }

}