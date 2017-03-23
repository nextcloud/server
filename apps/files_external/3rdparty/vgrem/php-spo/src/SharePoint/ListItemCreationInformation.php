<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * Specifies the properties of the new list item.
 */
class ListItemCreationInformation extends ClientValueObject
{
    /**
     * Gets or sets a value that specifies the folder for the new list item.
     * @var string
     */
    public $FolderUrl;

    /**
     * Gets or sets a value that specifies the name of the new list item. It must be the name of the file if the parent list of the list item is a document library.
     * @var string
     */
    public $LeafName;

    /**
     * Gets or sets a value that specifies whether the new list item is a file or a folder.
     * @var int
     */
    public $UnderlyingObjectType;
}