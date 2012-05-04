<?php

/**
 * CurrentUserPrivilegeSet
 *
 * This class represents the current-user-privilege-set property. When
 * requested, it contain all the privileges a user has on a specific node.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Property_CurrentUserPrivilegeSet extends Sabre_DAV_Property {

    /**
     * List of privileges
     *
     * @var array
     */
    private $privileges;

    /**
     * Creates the object
     *
     * Pass the privileges in clark-notation
     *
     * @param array $privileges
     */
    public function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    /**
     * Serializes the property in the DOM
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;
        foreach($this->privileges as $privName) {

            $this->serializePriv($doc,$node,$privName);

        }

    }

    /**
     * Serializes one privilege
     *
     * @param DOMDocument $doc
     * @param DOMElement $node
     * @param string $privName
     * @return void
     */
    protected function serializePriv($doc,$node,$privName) {

        $xp  = $doc->createElementNS('DAV:','d:privilege');
        $node->appendChild($xp);

        $privParts = null;
        preg_match('/^{([^}]*)}(.*)$/',$privName,$privParts);

        $xp->appendChild($doc->createElementNS($privParts[1],'d:'.$privParts[2]));

    }

}
