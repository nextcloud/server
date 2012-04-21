<?php

/**
 * PreconditionFailed
 *
 * This exception is normally thrown when a client submitted a conditional request,
 * like for example an If, If-None-Match or If-Match header, which caused the HTTP
 * request to not execute (the condition of the header failed)
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Exception_PreconditionFailed extends Sabre_DAV_Exception {

    /**
     * When this exception is thrown, the header-name might be set.
     *
     * This allows the exception-catching code to determine which HTTP header
     * caused the exception.
     *
     * @var string
     */
    public $header = null;

    /**
     * Create the exception
     *
     * @param string $message
     * @param string $header
     */
    public function __construct($message, $header=null) {

        parent::__construct($message);
        $this->header = $header;

    }

    /**
     * Returns the HTTP statuscode for this exception
     *
     * @return int
     */
    public function getHTTPCode() {

        return 412;

    }

    /**
     * This method allows the exception to include additional information into the WebDAV error response
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $errorNode
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {

        if ($this->header) {
            $prop = $errorNode->ownerDocument->createElement('s:header');
            $prop->nodeValue = $this->header;
            $errorNode->appendChild($prop);
        }

    }

}
