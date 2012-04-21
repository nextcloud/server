<?php

/**
 * NeedPrivileges
 *
 * The 403-need privileges is thrown when a user didn't have the appropriate
 * permissions to perform an operation
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Exception_NeedPrivileges extends Sabre_DAV_Exception_Forbidden {

    /**
     * The relevant uri
     *
     * @var string
     */
    protected $uri;

    /**
     * The privileges the user didn't have.
     *
     * @var array
     */
    protected $privileges;

    /**
     * Constructor
     *
     * @param string $uri
     * @param array $privileges
     */
    public function __construct($uri,array $privileges) {

        $this->uri = $uri;
        $this->privileges = $privileges;

        parent::__construct('User did not have the required privileges (' . implode(',', $privileges) . ') for path "' . $uri . '"');

    }

    /**
     * Adds in extra information in the xml response.
     *
     * This method adds the {DAV:}need-privileges element as defined in rfc3744
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $errorNode
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {

        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:','d:need-privileges');
        $errorNode->appendChild($np);

        foreach($this->privileges as $privilege) {

            $resource = $doc->createElementNS('DAV:','d:resource');
            $np->appendChild($resource);

            $resource->appendChild($doc->createElementNS('DAV:','d:href',$server->getBaseUri() . $this->uri));

            $priv = $doc->createElementNS('DAV:','d:privilege');
            $resource->appendChild($priv);

            preg_match('/^{([^}]*)}(.*)$/',$privilege,$privilegeParts);
            $priv->appendChild($doc->createElementNS($privilegeParts[1],'d:' . $privilegeParts[2]));


        }

    }

}

