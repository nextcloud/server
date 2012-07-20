<?php

/**
 * UnSupportedMediaType
 *
 * The 415 Unsupported Media Type status code is generally sent back when the client
 * tried to call an HTTP method, with a body the server didn't understand
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Exception_UnsupportedMediaType extends Sabre_DAV_Exception {

    /**
     * returns the http statuscode for this exception
     *
     * @return int
     */
    public function getHTTPCode() {

        return 415;

    }

}
