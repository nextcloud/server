<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\Uri;

/**
 * Principals Collection.
 *
 * This is a helper class that easily allows you to create a collection that
 * has a childnode for every principal.
 *
 * To use this class, simply implement the getChildForPrincipal method.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class AbstractPrincipalCollection extends DAV\Collection implements IPrincipalCollection
{
    /**
     * Principal backend.
     *
     * @var PrincipalBackend\BackendInterface
     */
    protected $principalBackend;

    /**
     * The path to the principals we're listing from.
     *
     * @var string
     */
    protected $principalPrefix;

    /**
     * If this value is set to true, it effectively disables listing of users
     * it still allows user to find other users if they have an exact url.
     *
     * @var bool
     */
    public $disableListing = false;

    /**
     * Creates the object.
     *
     * This object must be passed the principal backend. This object will
     * filter all principals from a specified prefix ($principalPrefix). The
     * default is 'principals', if your principals are stored in a different
     * collection, override $principalPrefix
     *
     * @param string $principalPrefix
     */
    public function __construct(PrincipalBackend\BackendInterface $principalBackend, $principalPrefix = 'principals')
    {
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
     * @return DAV\INode
     */
    abstract public function getChildForPrincipal(array $principalInfo);

    /**
     * Returns the name of this collection.
     *
     * @return string
     */
    public function getName()
    {
        list(, $name) = Uri\split($this->principalPrefix);

        return $name;
    }

    /**
     * Return the list of users.
     *
     * @return array
     */
    public function getChildren()
    {
        if ($this->disableListing) {
            throw new DAV\Exception\MethodNotAllowed('Listing members of this collection is disabled');
        }
        $children = [];
        foreach ($this->principalBackend->getPrincipalsByPrefix($this->principalPrefix) as $principalInfo) {
            $children[] = $this->getChildForPrincipal($principalInfo);
        }

        return $children;
    }

    /**
     * Returns a child object, by its name.
     *
     * @param string $name
     *
     * @throws DAV\Exception\NotFound
     *
     * @return DAV\INode
     */
    public function getChild($name)
    {
        $principalInfo = $this->principalBackend->getPrincipalByPath($this->principalPrefix.'/'.$name);
        if (!$principalInfo) {
            throw new DAV\Exception\NotFound('Principal with name '.$name.' not found');
        }

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
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return a list of 'child names', which may be
     * used to call $this->getChild in the future.
     *
     * @param string $test
     *
     * @return array
     */
    public function searchPrincipals(array $searchProperties, $test = 'allof')
    {
        $result = $this->principalBackend->searchPrincipals($this->principalPrefix, $searchProperties, $test);
        $r = [];

        foreach ($result as $row) {
            list(, $r[]) = Uri\split($row);
        }

        return $r;
    }

    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or null, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     *
     * @return string
     */
    public function findByUri($uri)
    {
        return $this->principalBackend->findByUri($uri, $this->principalPrefix);
    }
}
