<?php

/**
 * AclRestrictions property
 *
 * This property represents {DAV:}acl-restrictions, as defined in RFC3744.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Property_AclRestrictions extends Sabre_DAV_Property {

    /**
     * Serializes the property into a DOMElement
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $elem
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $elem) {

        $doc = $elem->ownerDocument;

        $elem->appendChild($doc->createElementNS('DAV:','d:grant-only'));
        $elem->appendChild($doc->createElementNS('DAV:','d:no-invert'));

    }

}
