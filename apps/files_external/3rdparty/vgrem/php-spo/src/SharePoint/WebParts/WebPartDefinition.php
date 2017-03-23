<?php


namespace Office365\PHP\Client\SharePoint\WebParts;

use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientObject;


class WebPartDefinition extends ClientObject
{

    public function saveWebPartChanges(){
        $qry = new ClientActionInvokePostMethod(
            $this,
            "SaveWebPartChanges"
        );
        $this->getContext()->addQuery($qry);
    }

    public function closeWebPart()
    {
        $qry = new ClientActionInvokePostMethod(
            $this,
            "CloseWebPart"
        );
        $this->getContext()->addQuery($qry);
    }


    /**
     * @return WebPart
     */
    public function getWebPart()
    {
        if (!$this->isPropertyAvailable('WebPart')) {
            $this->setProperty('WebPart', new WebPart($this->getContext(), $this->getResourcePath(), "WebPart"));
        }
        return $this->getProperty('WebPart');
    }
    
}