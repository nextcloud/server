<?php


namespace Office365\PHP\Client\SharePoint\UserProfiles;

use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientRuntimeContext;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

class UserProfile extends ClientObject
{

    public function __construct(ClientRuntimeContext $ctx)
    {
        parent::__construct($ctx, new ResourcePathEntity($ctx,null,"sp.UserProfiles.profileloader.getprofileloader/getuserprofile"));
    }

    /**
     * Enqueues creating a personal site for this user, which can be used to share documents, web pages, and other files.
     */
    public function createPersonalSiteEnque(){
        $qry = new ClientActionInvokePostMethod(
            $this,
            "createpersonalsiteenque",
            array(false)
        );
        $this->getContext()->addQuery($qry);
    }

}