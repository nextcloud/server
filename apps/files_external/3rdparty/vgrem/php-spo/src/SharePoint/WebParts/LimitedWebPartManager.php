<?php


namespace Office365\PHP\Client\SharePoint\WebParts;

use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;
use Office365\PHP\Client\Runtime\OData\ODataPayload;
use Office365\PHP\Client\Runtime\OData\ODataPayloadKind;


class LimitedWebPartManager extends ClientObject
{

    /**
     * Imports a Web Part from a string in the .dwp format
     * @param string $webPartXml
     * @return WebPartDefinition
     */
    public function importWebPart($webPartXml)
    {
        $payload = new ODataPayload(array("webPartXml" => $webPartXml),ODataPayloadKind::Entity,$this->getEntityTypeName());
        $webPartDefinition = new WebPartDefinition($this->getContext());
        $qry = new ClientActionInvokePostMethod(
            $this,
            "ImportWebPart",
            null,
            $payload
        );
        $this->getContext()->addQuery($qry,$webPartDefinition);
        return $webPartDefinition;
    }


    /**
     * @return WebPartDefinitionCollection
     */
    public function getWebParts()
    {
        if(!$this->isPropertyAvailable('WebParts')){
            $this->setProperty(
                "WebParts", 
                new WebPartDefinitionCollection($this->getContext(),new ResourcePathEntity($this->getContext(),$this->getResourcePath(), "WebParts"))
            );
        }
        return $this->getProperty("WebParts");
    }

}
