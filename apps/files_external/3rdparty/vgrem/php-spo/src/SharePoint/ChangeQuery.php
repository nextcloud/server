<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientValueObject;


/**
 * Defines a query that is performed against the change log.
 */
class ChangeQuery extends ClientValueObject
{
    function __construct()
    {
        $this->Add = false;
        $this->Alert = false;
        $this->ContentType = false;
        $this->DeleteObject = false;
        $this->Field = false;
        $this->File = false;
        $this->Folder = false;
        $this->Group = false;
        $this->Web = false;
        $this->Update = false;
        $this->GroupMembershipAdd = false;
        $this->GroupMembershipDelete = false;
        $this->Item = false;
        $this->List = false;
        $this->Move = false;
        $this->Navigation = false;
        $this->Rename = false;
        $this->Restore = false;
        $this->RoleAssignmentAdd = false;
        $this->RoleAssignmentDelete = false;
        $this->RoleDefinitionAdd = false;
        $this->RoleDefinitionDelete = false;
        $this->RoleDefinitionUpdate = false;
        $this->SecurityPolicy = false;
        $this->Site = false;
        $this->SystemUpdate = false;
        $this->User = false;
        $this->View = false;
        parent::__construct();
    }


    /**
     * Gets or sets a value that specifies whether add changes are included in the query.
     * @var boolean
     */
    public $Add;

    /**
     * Gets or sets a value that specifies whether changes to alerts are included in the query.
     * @var bool
     */
    public $Alert;


    /**
     * Gets or sets a value that specifies the end date and end time for changes that are returned through the query.
     * @var ChangeToken
     */
    public $ChangeTokenEnd;


    /**
     * Gets or sets a value that specifies the start date and start time for changes that are returned through the query.
     * @var boolean
     */
    public $ChangeTokenStart;


    /**
     * Gets or sets a value that specifies whether changes to content types are included in the query.
     * @var boolean
     */
    public $ContentType;


    /**
     * Gets or sets a value that specifies whether changes to content types are included in the query.
     * @var boolean
     */
    public $DeleteObject;

    /**
     * Gets or sets a value that specifies whether changes to content types are included in the query.
     * @var boolean
     */
    public $Field;


    /**
     * Gets or sets a value that specifies whether changes to content types are included in the query.
     * @var boolean
     */
    public $File;


    /**
     * Gets or sets value that specifies whether changes to folders are included in the query.
     * @var boolean
     */
    public $Folder;


    /**
     * Gets or sets a value that specifies whether changes to groups are included in the query.
     * @var boolean
     */
    public $Group;


    /**
     * Gets or sets a value that specifies whether adding users to groups is included in the query.
     * @var bool
     */
    public $GroupMembershipAdd;


    /**
     * Gets or sets a value that specifies whether deleting users from the groups is included in the query.
     * @var  bool
     */
    public $GroupMembershipDelete;


    /**
     * Gets or sets a value that specifies whether general changes to list items are included in the query.
     * @var bool
     */
    public $Item;


    /**
     * Gets or sets a value that specifies whether changes to lists are included in the query.
     * @var bool
     */
    public $List;


    /**
     * Gets or sets a value that specifies whether move changes are included in the query.
     * @var boolean
     */
    public $Move;


    /**
     * Gets or sets a value that specifies whether changes to the navigation structure of a site collection are included in the query.
     * @var boolean
     */
    public $Navigation;


    /**
     * Gets or sets a value that specifies whether renaming changes are included in the query.
     * @var boolean
     */
    public $Rename;


    /**
     * Gets or sets a value that specifies whether restoring items from the recycle bin or from backups is included in the query.
     * @var bool
     */
    public $Restore;


    /**
     * Gets or sets a value that specifies whether adding role assignments is included in the query.
     * @var bool
     */
    public $RoleAssignmentAdd;


    /**
     * Gets or sets a value that specifies whether adding role assignments is included in the query.
     * @var bool
     */
    public $RoleAssignmentDelete;


    /**
     * Gets or sets a value that specifies whether adding role assignments is included in the query.
     * @var bool
     */
    public $RoleDefinitionAdd;


    /**
     * Gets or sets a value that specifies whether adding role assignments is included in the query.
     * @var bool
     */
    public $RoleDefinitionDelete;


    /**
     * Gets or sets a value that specifies whether adding role assignments is included in the query.
     * @var bool
     */
    public $RoleDefinitionUpdate;


    /**
     * Gets or sets a value that specifies whether modifications to security policies are included in the query.
     * @var bool
     */
    public $SecurityPolicy;


    /**
     * Gets or sets a value that specifies whether changes to site collections are included in the query.
     * @var bool
     */
    public $Site;


    /**
     * Gets or sets a value that specifies whether updates made using the item SystemUpdate method are included in the query.
     * @var bool
     */
    public $SystemUpdate;


    /**
     * Gets or sets a value that specifies whether update changes are included in the query.
     * @var bool
     */
    public $Update;


    /**
     * Gets or sets a value that specifies whether changes to users are included in the query.
     * @var bool
     */
    public $User;


    /**
     * Gets or sets a value that specifies whether changes to views are included in the query.
     * @var bool
     */
    public $View;


    /**
     * Gets or sets a value that specifies whether changes to Web sites are included in the query.
     * @var boolean
     */
    public $Web;
}