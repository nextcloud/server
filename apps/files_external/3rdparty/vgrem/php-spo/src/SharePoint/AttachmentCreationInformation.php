<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientValueObject;

class AttachmentCreationInformation extends ClientValueObject
{

    /**
     * @var string
     */
    public $FileName;


    /**
     * @var string
     */
    public $ContentStream;

}