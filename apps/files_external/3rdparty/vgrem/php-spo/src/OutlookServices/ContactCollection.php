<?php


namespace Office365\PHP\Client\OutlookServices;

use Office365\PHP\Client\Runtime\ClientActionCreateEntity;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

class ContactCollection extends ClientObjectCollection
{

    /**
     * Creates Contact resource
     * @return Contact
     */
    public function createContact() {
        $contact = new Contact($this->getContext());
        $qry = new ClientActionCreateEntity($this, $contact);
        $this->getContext()->addQuery($qry, $contact);
        $this->addChild($contact);
        return $contact;
    }




    /**
     * Get a contact by using the contact ID.
     * @param string $contactId
     * @return Contact
     */
    function getById($contactId){
        return new Contact(
            $this->getContext(),
            new ResourcePathEntity($this->getContext(),$this->getResourcePath(),$contactId)
        );
    }
}