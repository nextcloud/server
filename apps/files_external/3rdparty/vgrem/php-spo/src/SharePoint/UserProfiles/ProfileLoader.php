<?php

namespace Office365\PHP\Client\SharePoint\UserProfiles;


use Office365\PHP\Client\Runtime\ClientRuntimeContext;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

/**
 * Provides an alternate entry point to user profiles rather than calling methods directly.
 */
class ProfileLoader extends ClientObject
{

    public function __construct(ClientRuntimeContext $ctx)
    {
        parent::__construct($ctx,new ResourcePathEntity($ctx,null,"sp.UserProfiles.profileloader"));
    }




}