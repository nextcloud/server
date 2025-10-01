<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;

/**
 * Principal Collection interface.
 *
 * Implement this interface to ensure that your principal collection can be
 * searched using the principal-property-search REPORT.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IPrincipalCollection extends DAV\ICollection
{
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
    public function searchPrincipals(array $searchProperties, $test = 'allof');

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
    public function findByUri($uri);
}
