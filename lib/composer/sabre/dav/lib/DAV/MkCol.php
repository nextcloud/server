<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * This class represents a MKCOL operation.
 *
 * MKCOL creates a new collection. MKCOL comes in two flavours:
 *
 * 1. MKCOL with no body, signifies the creation of a simple collection.
 * 2. MKCOL with a request body. This can create a collection with a specific
 *    resource type, and a set of properties that should be set on the new
 *    collection. This can be used to create caldav calendars, carddav address
 *    books, etc.
 *
 * Property updates must always be atomic. This means that a property update
 * must either completely succeed, or completely fail.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class MkCol extends PropPatch
{
    /**
     * A list of resource-types in clark-notation.
     *
     * @var array
     */
    protected $resourceType;

    /**
     * Creates the MKCOL object.
     *
     * @param string[] $resourceType list of resourcetype values
     * @param array    $mutations    list of new properties values
     */
    public function __construct(array $resourceType, array $mutations)
    {
        $this->resourceType = $resourceType;
        parent::__construct($mutations);
    }

    /**
     * Returns the resourcetype of the new collection.
     *
     * @return string[]
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Returns true or false if the MKCOL operation has at least the specified
     * resource type.
     *
     * If the resourcetype is specified as an array, all resourcetypes are
     * checked.
     *
     * @param string|string[] $resourceType
     *
     * @return bool
     */
    public function hasResourceType($resourceType)
    {
        return 0 === count(array_diff((array) $resourceType, $this->resourceType));
    }
}
