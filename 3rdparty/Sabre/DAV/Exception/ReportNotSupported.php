<?php

/**
 * ReportNotSupported
 *
 * This exception is thrown when the client requested an unknown report through the REPORT method
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Exception_ReportNotSupported extends Sabre_DAV_Exception_Forbidden {

    /**
     * This method allows the exception to include additional information into the WebDAV error response
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $errorNode
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {

        $error = $errorNode->ownerDocument->createElementNS('DAV:','d:supported-report');
        $errorNode->appendChild($error);

    }

}
