<?php


namespace Office365\PHP\Client\OneNote;


use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

class Me extends ClientObject
{

    /**
     * @return Notes
     */
    public function getNotes()
    {
        if (!$this->isPropertyAvailable("Notes")) {
            $this->setProperty("Notes",
                new Notes($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Notes"
                )));
        }
        return $this->getProperty("Notes");
    }


}