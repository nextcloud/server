<?php

namespace Office365\PHP\Client\OutlookServices;


/**
 * A contact, which is an item in Outlook for users to organize and save information about the people and organizations
 * that they communicate with. Contacts are contained in contact folders.
 */
class Contact extends Item
{

    /**
     * The name of the contact's assistant.
     * @var string
     */
    public $AssistantName;


    /**
     * The ID of the contact's parent folder.
     * @var string
     */
    public $ParentFolderId;


    /**
     * The contact's birthday.
     * @var string
     */
    public $Birthday;


    /**
     * The contact's given name.
     * @var string
     */
    public $GivenName;


    /**
     * The contact's initials.
     * @var string
     */
    public $Initials;


    /**
     * The contact's surname.
     * @var string
     */
    public $Surname;


    /**
     * The contact's job title.
     * @var string
     */
    public $JobTitle;


    /**
     * @var string
     */
    public $Department;


    /**
     * @var array
     */
    public $BusinessPhones;


    /**
     * @var string
     */
    public $MobilePhone1;


    /**
     * The contact's email addresses.
     * @var array
     */
    public $EmailAddresses;

    /**
     * The contact's generation.
     * @var string
     */
    public $Generation;


    /**
     * The contact's home address.
     * @var PhysicalAddress
     */
    public $HomeAddress;


    /**
     * @var array
     */
    public $HomePhones;


    /**
     * The contact's instant messaging (IM) addresses.
     * @var array
     */
    public $ImAddresses;

    /**
     * The name of the contact's manager.
     * @var string
     */
    public $Manager;


    /**
     * The contact's middle name.
     * @var string
     */
    public $MiddleName;


    /**
     * The contact's nickname.
     * @var string
     */
    public $NickName;
}