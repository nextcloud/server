<?php

/**
 * AddressBook rootnode
 *
 * This object lists a collection of users, which can contain addressbooks.
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_AddressBookRoot extends Sabre_DAVACL_AbstractPrincipalCollection {

    /**
     * Principal Backend
     *
     * @var Sabre_DAVACL_IPrincipalBackend
     */
    protected $principalBackend;

    /**
     * CardDAV backend
     *
     * @var Sabre_CardDAV_Backend_Abstract
     */
    protected $carddavBackend;

    /**
     * Constructor
     *
     * This constructor needs both a principal and a carddav backend.
     *
     * By default this class will show a list of addressbook collections for
     * principals in the 'principals' collection. If your main principals are
     * actually located in a different path, use the $principalPrefix argument
     * to override this.
     *
     * @param Sabre_DAVACL_IPrincipalBackend $principalBackend
     * @param Sabre_CardDAV_Backend_Abstract $carddavBackend
     * @param string $principalPrefix
     */
    public function __construct(Sabre_DAVACL_IPrincipalBackend $principalBackend,Sabre_CardDAV_Backend_Abstract $carddavBackend, $principalPrefix = 'principals') {

        $this->carddavBackend = $carddavBackend;
        parent::__construct($principalBackend, $principalPrefix);

    }

    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName() {

        return Sabre_CardDAV_Plugin::ADDRESSBOOK_ROOT;

    }

    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     *
     * @param array $principal
     * @return Sabre_DAV_INode
     */
    public function getChildForPrincipal(array $principal) {

        return new Sabre_CardDAV_UserAddressBooks($this->carddavBackend, $principal['uri']);

    }

}
