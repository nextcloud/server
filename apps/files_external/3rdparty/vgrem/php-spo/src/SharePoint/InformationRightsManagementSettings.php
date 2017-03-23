<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientObject;

class InformationRightsManagementSettings extends ClientObject
{
    public $AllowPrint;

    public $AllowScript;

    public $AllowWriteCopy;

    public $DisableDocumentBrowserView;

    public $DocumentAccessExpireDays;

    public $DocumentLibraryProtectionExpireDate;

    public $EnableDocumentAccessExpire;

    public $EnableDocumentBrowserPublishingView;

    public $EnableGroupProtection;

    public $EnableLicenseCacheExpire;

    public $GroupName;

    public $LicenseCacheExpireDays;

    public $PolicyDescription;

    public $PolicyTitle;
}