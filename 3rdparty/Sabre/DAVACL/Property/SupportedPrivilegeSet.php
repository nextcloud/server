<?php

/**
 * SupportedPrivilegeSet property
 *
 * This property encodes the {DAV:}supported-privilege-set property, as defined
 * in rfc3744. Please consult the rfc for details about it's structure.
 *
 * This class expects a structure like the one given from
 * Sabre_DAVACL_Plugin::getSupportedPrivilegeSet as the argument in its
 * constructor.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Property_SupportedPrivilegeSet extends Sabre_DAV_Property {

    /**
     * privileges
     *
     * @var array
     */
    private $privileges;

    /**
     * Constructor
     *
     * @param array $privileges
     */
    public function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    /**
     * Serializes the property into a domdocument.
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;
        $this->serializePriv($doc, $node, $this->privileges);

    }

    /**
     * Serializes a property
     *
     * This is a recursive function.
     *
     * @param DOMDocument $doc
     * @param DOMElement $node
     * @param array $privilege
     * @return void
     */
    private function serializePriv($doc,$node,$privilege) {

        $xsp = $doc->createElementNS('DAV:','d:supported-privilege');
        $node->appendChild($xsp);

        $xp  = $doc->createElementNS('DAV:','d:privilege');
        $xsp->appendChild($xp);

        $privParts = null;
        preg_match('/^{([^}]*)}(.*)$/',$privilege['privilege'],$privParts);

        $xp->appendChild($doc->createElementNS($privParts[1],'d:'.$privParts[2]));

        if (isset($privilege['abstract']) && $privilege['abstract']) {
            $xsp->appendChild($doc->createElementNS('DAV:','d:abstract'));
        }

        if (isset($privilege['description'])) {
            $xsp->appendChild($doc->createElementNS('DAV:','d:description',$privilege['description']));
        }

        if (isset($privilege['aggregates'])) {
            foreach($privilege['aggregates'] as $subPrivilege) {
                $this->serializePriv($doc,$xsp,$subPrivilege);
            }
        }

    }

}
