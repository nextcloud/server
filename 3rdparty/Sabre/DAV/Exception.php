<?php

/**
 * SabreDAV base exception
 *
 * This is SabreDAV's base exception file, use this to implement your own exception.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * Main Exception class.
 *
 * This class defines a getHTTPCode method, which should return the appropriate HTTP code for the Exception occurred.
 * The default for this is 500.
 *
 * This class also allows you to generate custom xml data for your exceptions. This will be displayed
 * in the 'error' element in the failing response.
 */
class Sabre_DAV_Exception extends Exception {

    /**
     * Returns the HTTP statuscode for this exception
     *
     * @return int
     */
    public function getHTTPCode() {

        return 500;

    }

    /**
     * This method allows the exception to include additional information into the WebDAV error response
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $errorNode
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {


    }

    /**
     * This method allows the exception to return any extra HTTP response headers.
     *
     * The headers must be returned as an array.
     *
     * @param Sabre_DAV_Server $server
     * @return array
     */
    public function getHTTPHeaders(Sabre_DAV_Server $server) {

        return array();

    }

}

