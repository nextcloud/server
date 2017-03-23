<?php

namespace Office365\PHP\Client\SharePoint\Social;

use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * Represents a user, document, site, or tag.
 */
class SocialRestActor extends ClientValueObject
{

    /**
     * @var SocialActor The specified user.
     * Returns null if the user is the current user or if the resource is not a user-type actor.
     */
    public $FollowableItemActor;


    /**
     * @var SocialActor The current user.
     */
    public $Me;

}