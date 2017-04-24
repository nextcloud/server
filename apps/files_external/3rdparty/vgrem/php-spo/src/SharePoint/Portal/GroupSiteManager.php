<?php


namespace Office365\PHP\Client\SharePoint\Portal;


use Office365\PHP\Client\Runtime\ClientAction;
use Office365\PHP\Client\Runtime\ClientActionType;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\OData\ODataMetadataLevel;
use Office365\PHP\Client\Runtime\OperationParameterCollection;
use Office365\PHP\Client\Runtime\ResourcePathEntity;
use Office365\PHP\Client\SharePoint\ClientContext;


class GroupSiteManager extends ClientObject
{

    public function __construct(ClientContext $ctx)
    {
        $ctx->Format->MetadataLevel = ODataMetadataLevel::NoMetadata;
        parent::__construct($ctx,new ResourcePathEntity($ctx,null,"GroupSiteManager"));

    }

    /**
     * @param string $displayName
     * @param string $alias
     * @param boolean $isPublic
     * @param string $description
     * @param null $additionalOwners
     * @return GroupSiteInfo
     */
    public function createGroupEx($displayName,$alias,$isPublic,$description="",$additionalOwners=null) {
        $payload = new OperationParameterCollection();
        $payload->add("displayName",$displayName);
        $payload->add("alias",$alias);
        $payload->add("isPublic",$isPublic);


        $info = new GroupSiteInfo();
        $qry = new ClientAction($this->getResourceUrl() . "/CreateGroupEx", $payload,ClientActionType::CreateEntity);
        $this->getContext()->addQuery($qry,$info);
        return $info;
    }



}