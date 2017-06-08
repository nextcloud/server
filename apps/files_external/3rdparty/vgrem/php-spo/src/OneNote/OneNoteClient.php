<?php

namespace Office365\PHP\Client\OneNote;


use Office365\PHP\Client\Runtime\Auth\IAuthenticationContext;
use Office365\PHP\Client\Runtime\ClientAction;
use Office365\PHP\Client\Runtime\ClientActionType;
use Office365\PHP\Client\Runtime\ClientRuntimeContext;
use Office365\PHP\Client\Runtime\HttpMethod;
use Office365\PHP\Client\Runtime\OData\JsonFormat;
use Office365\PHP\Client\Runtime\OData\ODataMetadataLevel;
use Office365\PHP\Client\Runtime\Office365Version;
use Office365\PHP\Client\Runtime\ResourcePathEntity;
use Office365\PHP\Client\Runtime\Utilities\RequestOptions;

class OneNoteClient extends ClientRuntimeContext
{

    public function __construct(IAuthenticationContext $authContext, $version = Office365Version::V1)
    {
        $this->version = $version;
        $this->serviceRootUrl = $this->serviceRootUrl . $version . "/";
        parent::__construct($this->serviceRootUrl, $authContext, new JsonFormat(ODataMetadataLevel::NoMetadata), $version);
    }



    /**
     * Submits query to OneNote REST/OData service
     */
    public function executeQuery()
    {
        $this->getPendingRequest()->beforeExecuteQuery(function (RequestOptions $request,ClientAction $query){
            $this->prepareOutlookServicesRequest($request,$query);
        });
        parent::executeQuery();
    }





    private function prepareOutlookServicesRequest(RequestOptions $request,ClientAction $query)
    {
        //set data modification headers
        if ($query->ActionType == ClientActionType::UpdateEntity) {
            $request->Method = HttpMethod::Patch;
        } else if ($query->ActionType == ClientActionType::DeleteEntity) {
            $request->Method = HttpMethod::Delete;
        }
    }


    /**
     * @return Me
     */
    public function getMe(){
        if(!isset($this->me))
            $this->me = new Me($this,new ResourcePathEntity($this,null,"Me"));
        return $this->me;
    }


    /**
     * @return MyOrganization
     */
    public function getMyOrganization(){
        if(!isset($this->myOrg))
            $this->myOrg = new MyOrganization($this,new ResourcePathEntity($this,null,"MyOrganization"));
        return $this->myOrg;
    }


    /**
     * @var Me
     */
    private $me;


    /**
     * @var MyOrganization
     */
    private $myOrg;


    /**
     * @var string
     */
    private $serviceRootUrl = "https://www.onenote.com/api/";

    /**
     * @var string
     */
    public $version;

}