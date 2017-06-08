<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientValueObject;

class ListCreationInformation extends ClientValueObject
{
    /**
     * @var string
     */
    public $Title;

    /**
     * @var string
     */
    public $Description;

    /**
     * @var ListTemplateType
     */
    public $BaseTemplate;

    /**
     * @var bool
     */
    public $AllowContentTypes;

    /**
     * @var bool
     */
    public $ContentTypesEnabled;

    public function __construct($title)
    {
        $this->Title = $title;
        $this->Description = $title;
        $this->BaseTemplate = ListTemplateType::GenericList;
        $this->AllowContentTypes = true;
        $this->ContentTypesEnabled = true;
        parent::__construct("List");
    }

}
