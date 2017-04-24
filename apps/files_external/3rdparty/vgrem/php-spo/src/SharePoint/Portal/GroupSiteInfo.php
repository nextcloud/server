<?php


namespace Office365\PHP\Client\SharePoint\Portal;


use Office365\PHP\Client\Runtime\ClientValueObject;

class GroupSiteInfo extends ClientValueObject
{

    /**
     * @var string $DocumentsUrl
     */
    public $DocumentsUrl;


    /**
     * @var string $ErrorMessage
     */
    public $ErrorMessage;


    /**
     * @var string $GroupId
     */
    public $GroupId;


    /**
     * @var string $SiteStatus
     */
    public $SiteStatus;


    /**
     * @var string $SiteUrl
     */
    public $SiteUrl;

}