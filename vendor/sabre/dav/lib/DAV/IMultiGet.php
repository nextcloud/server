<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * IMultiGet.
 *
 * This interface adds a tiny bit of functionality to collections.
 *
 * There a certain situations, in particular in relation to WebDAV-Sync, CalDAV
 * and CardDAV, where information for a list of items will be requested.
 *
 * Because the getChild() call is the main abstraction method, this can in
 * reality result in many database calls, which could potentially be
 * optimized.
 *
 * The MultiGet interface is used by the server in these cases.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IMultiGet extends ICollection
{
    /**
     * This method receives a list of paths in it's first argument.
     * It must return an array with Node objects.
     *
     * If any children are not found, you do not have to return them.
     *
     * @param string[] $paths
     *
     * @return array
     */
    public function getMultipleChildren(array $paths);
}
