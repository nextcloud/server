<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * Specifies the properties used to create a new list view.
 */
class ViewCreationInformation extends ClientValueObject
{
    /**
     * @var string
     */
    public $Title;

    /**
     * @var bool
     */
    public $Paged;

    /**
     * @var string
     */
    public $PersonalView;

    /**
     * @var string
     */
    public $Query;

    /**
     * @var int
     */
    public $RowLimit;

    /**
     * @var bool
     */
    public $SetAsDefaultView;

    /**
     * @var string
     */
    public $ViewFields;

    /**
     * @var int
     */
    public $ViewTypeKind;


    public function __construct()
    {
        $this->RowLimit = 30;
        parent::__construct("View");
    }

}