<?php


namespace Office365\PHP\Client\OneDrive;


class File extends Item
{

    /**
     *
     * @return string
     */
    public function getContentUrl(){
        return $this->getProperty("contentUrl");
    }


    /**
     *
     * @param string $value
     */
    public function setContentUrl($value){
        return $this->setProperty("contentUrl",$value);
    }


    /**
     *
     * @return ImageFacet
     */
    public function getImage(){
        return $this->getProperty("image");
    }


    /**
     *
     * @param ImageFacet $value
     */
    public function setImage($value){
        return $this->setProperty("image",$value);
    }



    function getEntityTypeName()
    {
        return "#Microsoft.FileServices.File";
    }

}