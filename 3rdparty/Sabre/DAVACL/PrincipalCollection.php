<?php

/**
 * Principals Collection
 *
 * This collection represents a list of users. It uses
 * Sabre_DAV_Auth_Backend to determine which users are available on the list.
 *
 * The users are instances of Sabre_DAV_Auth_Principal
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_PrincipalCollection extends Sabre_DAVACL_AbstractPrincipalCollection {

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

        return new Sabre_DAVACL_Principal($this->principalBackend, $principal);

    }

}
