<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientValueObject;


/**
 * Represents metadata about site creation.
 */
class WebCreationInformation extends ClientValueObject
{

    /**
     * The description of the site.
     * @var string
     */
    public $Description;

    /**
     * The title of the site.
     * @var string
     */
    public $Title;


    /**
     * A valid language code identifier (LCID) of the language to use on the site.
     * @var int
     */
    public $Language;

    /**
     * The URL of the site.
     * @var string
     */
    public $Url;


    /**
     * Indicates whether the site inherits permissions from its parent.
     * @var boolean
     */
    public $UseSamePermissionsAsParentSite;


    /**
     * The name of the site template that is used to create the site.
     * @var string
     */
    public $WebTemplate;


    public function __construct($url,$title)
    {
        $this->Url = $url;
        $this->Title = $title;
        $this->Description = $title;
        $this->Language = 1033;
        $this->WebTemplate = "STS";
        $this->UseSamePermissionsAsParentSite = true;
        parent::__construct(null);
    }

}