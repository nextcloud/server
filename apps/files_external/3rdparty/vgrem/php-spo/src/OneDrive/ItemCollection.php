<?php


namespace Office365\PHP\Client\OneDrive;


use Office365\PHP\Client\Runtime\ClientAction;
use Office365\PHP\Client\Runtime\ClientActionType;
use Office365\PHP\Client\Runtime\ClientObjectCollection;

class ItemCollection extends ClientObjectCollection
{

    function add($name,$type,$content){
        $payload = new File($this->getContext());
        //$payload->setContent($content);
        $qry = new ClientAction($this->getResourceUrl() . "/add",$payload,ClientActionType::CreateEntity);
        $this->getContext()->addQuery($qry);
    }


}