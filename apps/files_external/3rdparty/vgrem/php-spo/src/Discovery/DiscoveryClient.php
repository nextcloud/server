<?php

namespace Office365\PHP\Client\Discovery;

use Office365\PHP\Client\Runtime\Auth\IAuthenticationContext;
use Office365\PHP\Client\Runtime\ClientActionReadEntity;
use Office365\PHP\Client\Runtime\ClientRuntimeContext;
use Office365\PHP\Client\Runtime\OData\JsonFormat;
use Office365\PHP\Client\Runtime\OData\ODataMetadataLevel;
use Office365\PHP\Client\Runtime\Office365Version;

class DiscoveryClient extends ClientRuntimeContext
{

    public function __construct(IAuthenticationContext $authContext, $version = Office365Version::V1)
    {
        $serviceRootUrl = "https://api.office.com/discovery/$version/";
        parent::__construct($serviceRootUrl, $authContext,new JsonFormat(ODataMetadataLevel::Verbose),$version);
    }



    /**
     * @return ServiceInfoCollection
     */
    public function getAllServices()
    {
        $allServices = new ServiceInfoCollection();
        $qry = new ClientActionReadEntity($this->getServiceRootUrl() . "me/allServices");
        $this->addQuery($qry,$allServices);
        return $allServices;
    }
}