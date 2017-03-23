<?php

namespace Office365\PHP\Client\OneDrive;

use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ResourcePathEntity;

class Drive extends ClientObject
{

    /**
     * Gets the user account that owns the drive.
     * @return Identity
     */
    public function getOwner(){
        return $this->getProperty("owner");
    }


    /**
     * Sets the user account that owns the drive.
     * @param string $value
     */
    public function setOwner($value){
        return $this->setProperty("owner",$value);
    }


    /**
     * @return ItemCollection
     */
    public function getFiles(){
        if (!$this->isPropertyAvailable("files")) {
            $this->setProperty("files",
                new ItemCollection(
                    $this->getContext(),
                    new ResourcePathEntity($this->getContext(),$this->getResourcePath(),"files")
                ));
        }
        return $this->getProperty("files");
    }






    /**
     * @param string $value
     */
    public function setFiles($value){
        return $this->setProperty("files",$value);
    }

}