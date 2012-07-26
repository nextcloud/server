<?php

/**
 * NotAuthenticated
 *
 * This exception is thrown when the client did not provide valid
 * authentication credentials.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Exception_NotAuthenticated extends Sabre_DAV_Exception {

    /**
     * Returns the HTTP statuscode for this exception
     *
     * @return int
     */
    public function getHTTPCode() {

        return 401;

    }

}
