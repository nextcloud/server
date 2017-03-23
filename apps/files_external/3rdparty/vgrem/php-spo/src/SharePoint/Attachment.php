<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientActionDeleteEntity;
use Office365\PHP\Client\Runtime\ClientObject;

class Attachment extends ClientObject
{

    public function deleteObject()
    {
        $qry = new ClientActionDeleteEntity($this);
        $this->getContext()->addQuery($qry);
    }

}