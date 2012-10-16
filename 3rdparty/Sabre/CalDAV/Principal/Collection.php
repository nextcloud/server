<?php

/**
 * Principal collection
 *
 * This is an alternative collection to the standard ACL principal collection.
 * This collection adds support for the calendar-proxy-read and
 * calendar-proxy-write sub-principals, as defined by the caldav-proxy
 * specification.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Principal_Collection extends Sabre_DAVACL_AbstractPrincipalCollection {

    /**
     * Returns a child object based on principal information
     *
     * @param array $principalInfo
     * @return Sabre_CalDAV_Principal_User
     */
    public function getChildForPrincipal(array $principalInfo) {

        return new Sabre_CalDAV_Principal_User($this->principalBackend, $principalInfo);

    }

}
