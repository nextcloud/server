<?php

/**
 * This class represents the {DAV:}supportedlock property
 *
 * This property contains information about what kind of locks
 * this server supports.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_SupportedLock extends Sabre_DAV_Property {

    /**
     * supportsLocks
     *
     * @var mixed
     */
    public $supportsLocks = false;

    /**
     * __construct
     *
     * @param mixed $supportsLocks
     */
    public function __construct($supportsLocks) {

        $this->supportsLocks = $supportsLocks;

    }

    /**
     * serialize
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement       $prop
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $prop) {

        $doc = $prop->ownerDocument;

        if (!$this->supportsLocks) return null;

        $lockEntry1 = $doc->createElementNS('DAV:','d:lockentry');
        $lockEntry2 = $doc->createElementNS('DAV:','d:lockentry');

        $prop->appendChild($lockEntry1);
        $prop->appendChild($lockEntry2);

        $lockScope1 = $doc->createElementNS('DAV:','d:lockscope');
        $lockScope2 = $doc->createElementNS('DAV:','d:lockscope');
        $lockType1 = $doc->createElementNS('DAV:','d:locktype');
        $lockType2 = $doc->createElementNS('DAV:','d:locktype');

        $lockEntry1->appendChild($lockScope1);
        $lockEntry1->appendChild($lockType1);
        $lockEntry2->appendChild($lockScope2);
        $lockEntry2->appendChild($lockType2);

        $lockScope1->appendChild($doc->createElementNS('DAV:','d:exclusive'));
        $lockScope2->appendChild($doc->createElementNS('DAV:','d:shared'));

        $lockType1->appendChild($doc->createElementNS('DAV:','d:write'));
        $lockType2->appendChild($doc->createElementNS('DAV:','d:write'));

        //$frag->appendXML('<d:lockentry><d:lockscope><d:exclusive /></d:lockscope><d:locktype><d:write /></d:locktype></d:lockentry>');
        //$frag->appendXML('<d:lockentry><d:lockscope><d:shared /></d:lockscope><d:locktype><d:write /></d:locktype></d:lockentry>');

    }

}

