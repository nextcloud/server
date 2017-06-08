<?php


namespace Office365\PHP\Client\OneDrive;


class Folder extends Item
{

    /**
     *
     * @return int
     */
    public function getChildCount(){
        return $this->getProperty("childCount");
    }


    /**
     *
     * @param int $value
     */
    public function setChildCount($value){
        return $this->setProperty("childCount",$value);
    }




}