<?php


namespace Office365\PHP\Client\OutlookServices;


use Office365\PHP\Client\Runtime\ClientValueObject;


/**
 * The body content of a message or event.
 */
class ItemBody extends ClientValueObject
{

    function __construct($contentType,$content)
    {
        $this->ContentType = $contentType;
        $this->Content = $content;
        parent::__construct();
    }

    /**
     * The content type: Text = 0, HTML = 1.
     * @var string
     */
    public $ContentType;


    /**
     * The text or HTML content.
     * @var string
     */
    public $Content;

}