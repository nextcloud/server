<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * Represents the content type identifier (ID) of a content type.
 */
class ContentTypeId extends ClientValueObject
{

    /**
     * @var string A string of hex characters that represents the content type ID.
     */
    public $StringValue;

}