<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\OData\ODataPayload;


/**
 * Web client object collection
 *
 */
class WebCollection extends ClientObjectCollection
{

    /**
     * @param WebCreationInformation $webCreationInformation
     * @return Web
     */
    public function add(WebCreationInformation $webCreationInformation)
    {
        $web = new Web($this->getContext(),$this->getResourcePath());
        $qry = new ClientActionInvokePostMethod(
            $this,
            "Add",
            null,
            $webCreationInformation->toParametersPayload()
        );
        $this->getContext()->addQuery($qry,$web);
        $this->addChild($web);
        return $web;
    }
}