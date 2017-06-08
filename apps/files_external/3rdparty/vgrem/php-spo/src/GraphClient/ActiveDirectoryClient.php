<?php

namespace Office365\PHP\Client\GraphClient;

use Office365\PHP\Client\Runtime\Auth\IAuthenticationContext;
use Office365\PHP\Client\Runtime\ClientAction;
use Office365\PHP\Client\Runtime\ClientRuntimeContext;
use Office365\PHP\Client\Runtime\OData\JsonLightFormat;
use Office365\PHP\Client\Runtime\OData\ODataMetadataLevel;
use Office365\PHP\Client\Runtime\ResourcePathEntity;
use Office365\PHP\Client\Runtime\Utilities\RequestOptions;

class ActiveDirectoryClient extends ClientRuntimeContext
{
    public function __construct($serviceRoot,IAuthenticationContext $authContext)
    {
        parent::__construct($serviceRoot, $authContext,new JsonLightFormat(ODataMetadataLevel::Verbose));
    }


    public function executeQuery()
    {
        $this->getPendingRequest()->beforeExecuteQuery(function (RequestOptions $request,ClientAction $query){
            $request->Url .= "?api-version=1.0";
        });
        parent::executeQuery();
    }



    public function getTenantDetails()
    {
        if(!isset($this->tenantDetails)){
            $this->tenantDetails = new TenantDetailCollection($this,new ResourcePathEntity($this,null,"tenantDetails"));
        }
        return $this->tenantDetails;
    }


    public function getDevices()
    {
        if(!isset($this->devices)){
            $this->devices = new DeviceCollection($this,new ResourcePathEntity($this,null,"devices"));
        }
        return $this->devices;
    }


    /**
     * @var TenantDetailCollection $tenantDetails
     */
    private $tenantDetails;

    /**
     * @var DeviceCollection $devices
     */
    private $devices;
}
