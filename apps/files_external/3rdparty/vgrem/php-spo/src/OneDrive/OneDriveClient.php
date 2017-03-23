<?php

namespace Office365\PHP\Client\OneDrive;

use Office365\PHP\Client\Runtime\Auth\IAuthenticationContext;
use Office365\PHP\Client\Runtime\ClientAction;
use Office365\PHP\Client\Runtime\ClientRuntimeContext;
use Office365\PHP\Client\Runtime\ContextWebInformation;
use Office365\PHP\Client\Runtime\OData\JsonFormat;
use Office365\PHP\Client\Runtime\OData\ODataMetadataLevel;
use Office365\PHP\Client\Runtime\Office365Version;
use Office365\PHP\Client\Runtime\ResourcePathEntity;
use Office365\PHP\Client\Runtime\Utilities\RequestOptions;

class OneDriveClient extends ClientRuntimeContext
{

    public function __construct($authorityUrl,IAuthenticationContext $authContext)
    {
        $serviceRootUrl = $authorityUrl . "/_api/" . Office365Version::V1 . "/";
        parent::__construct($serviceRootUrl, $authContext,new JsonFormat(ODataMetadataLevel::Verbose));
    }


    public function executeQuery()
    {
        $this->getPendingRequest()->beforeExecuteQuery(function (RequestOptions $request,ClientAction $query){

        });
        parent::executeQuery();
    }



    /**
     * @return CurrentUserRequestContext
     */
    public function getMe(){
        if(!isset($this->me))
            $this->me = new CurrentUserRequestContext($this,new ResourcePathEntity($this,null,"me"));
        return $this->me;
    }




}
