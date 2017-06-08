<?php
/**
 * Represents a collection of Folder resources.
 */

namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;

class FolderCollection extends ClientObjectCollection
{
    public function add($url)
    {
        $folder = new Folder($this->getContext());
        $folder->setProperty("ServerRelativeUrl", rawurlencode($url));
        $qry = new ClientActionCreateEntity($this, $folder);
        $this->getContext()->addQuery($qry, $folder);
        return $folder;
    }
}
