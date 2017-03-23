<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * Specifies properties that are used as parameters to initialize a new content type.
 */
class ContentTypeCreationInformation extends ClientValueObject
{
    public function __construct()
    {
        parent::__construct("ContentType");
    }


    public function setParentId($parentId){
        $this->ParentId = $parentId;
    }


    /**
     * formula: Parent content type ID + "00" + hexadecimal GUID
     * ref: https://msdn.microsoft.com/en-us/library/office/aa543822(v=office.14).aspx
     * @return string
     */
    /*function toJson()
    {
        if(!isset($this->Id)){
            if(isset($this->ParentId)){
                $this->Id = new ContentTypeId();
                $this->Id->StringValue = $this->ParentId . "00" . Guid::newGuid()->toString("N");
            }
        }
        return parent::toJson();
    }*/


    /**
     * Gets or sets a value that specifies the description of the content type that will be constructed.
     * @var string
     */
    public $Description;


    /**
     * Gets or sets a value that specifies the content type group of the content type that will be constructed.
     * @var string
     */
    public $Group;


    /**
     * @var ContentTypeId
     */
    public $Id;


    /**
     * @var string
     */
    public $Name;


    /**
     * @var string
     */
    private $ParentId;

}