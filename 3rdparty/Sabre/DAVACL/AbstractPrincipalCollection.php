<?php

/**
 * Principals Collection
 *
 * This is a helper class that easily allows you to create a collection that
 * has a childnode for every principal.
 *
 * To use this class, simply implement the getChildForPrincipal method.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAVACL_AbstractPrincipalCollection extends Sabre_DAV_Collection  {

    /**
     * Node or 'directory' name.
     *
     * @var string
     */
    protected $path;

    /**
     * Principal backend
     *
     * @var Sabre_DAVACL_IPrincipalBackend
     */
    protected $principalBackend;

    /**
     * If this value is set to true, it effectively disables listing of users
     * it still allows user to find other users if they have an exact url.
     *
     * @var bool
     */
    public $disableListing = false;

    /**
     * Creates the object
     *
     * This object must be passed the principal backend. This object will
     * filter all principals from a specified prefix ($principalPrefix). The
     * default is 'principals', if your principals are stored in a different
     * collection, override $principalPrefix
     *
     *
     * @param Sabre_DAVACL_IPrincipalBackend $principalBackend
     * @param string $principalPrefix
     */
    public function __construct(Sabre_DAVACL_IPrincipalBackend $principalBackend, $principalPrefix = 'principals') {

        $this->principalPrefix = $principalPrefix;
        $this->principalBackend = $principalBackend;

    }

    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     *
     * @param array $principalInfo
     * @return Sabre_DAVACL_IPrincipal
     */
    abstract function getChildForPrincipal(array $principalInfo);

    /**
     * Returns the name of this collection.
     *
     * @return string
     */
    public function getName() {

        list(,$name) = Sabre_DAV_URLUtil::splitPath($this->principalPrefix);
        return $name;

    }

    /**
     * Return the list of users
     *
     * @return array
     */
    public function getChildren() {

        if ($this->disableListing)
            throw new Sabre_DAV_Exception_MethodNotAllowed('Listing members of this collection is disabled');

        $children = array();
        foreach($this->principalBackend->getPrincipalsByPrefix($this->principalPrefix) as $principalInfo) {

            $children[] = $this->getChildForPrincipal($principalInfo);


        }
        return $children;

    }

    /**
     * Returns a child object, by its name.
     *
     * @param string $name
     * @throws Sabre_DAV_Exception_NotFound
     * @return Sabre_DAVACL_IPrincipal
     */
    public function getChild($name) {

        $principalInfo = $this->principalBackend->getPrincipalByPath($this->principalPrefix . '/' . $name);
        if (!$principalInfo) throw new Sabre_DAV_Exception_NotFound('Principal with name ' . $name . ' not found');
        return $this->getChildForPrincipal($principalInfo);

    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT. You should at least allow searching on
     * http://sabredav.org/ns}email-address.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * If multiple properties are being searched on, the search should be
     * AND'ed.
     *
     * This method should simply return a list of 'child names', which may be
     * used to call $this->getChild in the future.
     *
     * @param array $searchProperties
     * @return array
     */
    public function searchPrincipals(array $searchProperties) {

        $result = $this->principalBackend->searchPrincipals($this->principalPrefix, $searchProperties);
        $r = array();

        foreach($result as $row) {
            list(, $r[]) = Sabre_DAV_URLUtil::splitPath($row);
        }

        return $r;

    }

}
