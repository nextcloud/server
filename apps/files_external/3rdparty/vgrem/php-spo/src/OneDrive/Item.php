<?php


namespace Office365\PHP\Client\OneDrive;


use Office365\PHP\Client\Runtime\ClientObject;

class Item extends ClientObject
{

    /**
     *
     * @return string
     */
    public function getWebUrl(){
        return $this->getProperty("webUrl");
    }


    /**
     *
     * @param string $value
     */
    public function setWebUrl($value){
        return $this->setProperty("webUrl",$value);
    }



    /**
     *
     * @return ItemCollection
     */
    public function getChildren(){
        return $this->getProperty("children");
    }


    /**
     *
     * @param ItemCollection $value
     */
    public function setChildren($value){
        return $this->setProperty("children",$value);
    }


}