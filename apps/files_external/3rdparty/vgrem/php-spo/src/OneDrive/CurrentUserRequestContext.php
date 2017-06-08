<?php

namespace Office365\PHP\Client\OneDrive;

use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

class CurrentUserRequestContext extends ClientObject
{


    /**
     * @return Drive
     */
    public function getDrive()
    {
        if (!$this->isPropertyAvailable("Drive")) {
            $this->setProperty("Drive",
                new Drive($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Drive"
                )));
        }
        return $this->getProperty("Drive");
    }


    /**
     * @return FileCollection
     */
    public function getFiles()
    {
        if (!$this->isPropertyAvailable("Files")) {
            $this->setProperty("Files",
                new FileCollection($this->getContext(), new ResourcePathEntity(
                    $this->getContext(),
                    $this->getResourcePath(),
                    "Files"
                )));
        }
        return $this->getProperty("Files");
    }

}