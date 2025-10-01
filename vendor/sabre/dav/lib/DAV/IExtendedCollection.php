<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * The IExtendedCollection interface.
 *
 * This interface can be used to create special-type of collection-resources
 * as defined by RFC 5689.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IExtendedCollection extends ICollection
{
    /**
     * Creates a new collection.
     *
     * This method will receive a MkCol object with all the information about
     * the new collection that's being created.
     *
     * The MkCol object contains information about the resourceType of the new
     * collection. If you don't support the specified resourceType, you should
     * throw Exception\InvalidResourceType.
     *
     * The object also contains a list of WebDAV properties for the new
     * collection.
     *
     * You should call the handle() method on this object to specify exactly
     * which properties you are storing. This allows the system to figure out
     * exactly which properties you didn't store, which in turn allows other
     * plugins (such as the propertystorage plugin) to handle storing the
     * property for you.
     *
     * @param string $name
     *
     * @throws Exception\InvalidResourceType
     */
    public function createExtendedCollection($name, MkCol $mkCol);
}
