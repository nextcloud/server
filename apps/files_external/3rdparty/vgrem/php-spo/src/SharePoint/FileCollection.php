<?php

namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\ClientActionInvokePostMethod;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\OData\ODataPayload;
use Office365\PHP\Client\Runtime\OData\ODataPayloadKind;


/**
 * File collections
 *
 */
class FileCollection extends ClientObjectCollection
{

    /**
     * Creates a File resource
     * @param FileCreationInformation $fileCreationInformation
     * @return File
     */
    public function add(FileCreationInformation $fileCreationInformation)
    {
        $file = new File($this->getContext(),$this->getResourcePath());
        $qry = new ClientActionInvokePostMethod(
            $this,
            "add",
            array("overwrite"=>$fileCreationInformation->Overwrite,"url"=>rawurlencode($fileCreationInformation->Url)),
            $fileCreationInformation->Content
            );
        $this->getContext()->addQuery($qry,$file);
        //$this->addChild($file);
        return $file;
    }


    /**
     * Adds a ghosted file to an existing list or document library.
     * @param $urlOfFile
     * @param $templateFileType
     * @return File
     */
    public function addTemplateFile($urlOfFile,$templateFileType)
    {
        $file = new File($this->getContext(),$this->getResourcePath());
        $qry = new ClientActionInvokePostMethod(
            $this,
            "addTemplateFile",
            array(
                "urlOfFile" => $urlOfFile,
                "templateFileType" => (int)$templateFileType
            )
        );
        $this->getContext()->addQuery($qry,$file);
        return $file;
    }

}