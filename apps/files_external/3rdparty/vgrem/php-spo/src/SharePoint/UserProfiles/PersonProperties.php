<?php

namespace Office365\PHP\Client\SharePoint\UserProfiles;
use Office365\PHP\Client\Runtime\ClientObject;

/**
 * Represents user properties.
 */
class PersonProperties extends ClientObject
{

   

    /**
     * The user's account name.
     */
    public $AccountName;

    /**
     * The account names of the user's direct reports.
     */
    public $DirectReports;

    /**
     * The user's display name.
     */
    public $DisplayName;

    /**
     * The user's email address.
     */
    public $Email;

    /**
     * The account names of the user's manager hierarchy.
     */
    public $ExtendedManagers;

    /**
     * The account names of the user's extended reports.
     */
    public $ExtendedReports;


    /**
     * A Boolean value that indicates whether the user is being followed by the current user.
     */
    public $IsFollowed;


    /**
     * The user's latest microblog post.
     */
    public $LatestPost;


    /**
     * The account names of the user's peers.
     */
    public $Peers;


    /**
     * The absolute URL of the user's personal site.
     */
    public $PersonalUrl;


    /**
     * The absolute URL of the user's personal site.
     */
    public $PictureUrl;


    /**
     * The user's title.
     */
    public $Title;


    /**
     * The user profile properties for the user.
     */
    public $UserProfileProperties;


    /**
     * The URL of the user's profile page.
     */
    public $UserUrl;


}