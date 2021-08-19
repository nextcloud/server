<?php

declare(strict_types=1);

namespace Sabre\DAV\PropertyStorage\Backend;

use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;

/**
 * Propertystorage backend interface.
 *
 * Propertystorage backends must implement this interface to be used by the
 * propertystorage plugin.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface BackendInterface
{
    /**
     * Fetches properties for a path.
     *
     * This method received a PropFind object, which contains all the
     * information about the properties that need to be fetched.
     *
     * Usually you would just want to call 'get404Properties' on this object,
     * as this will give you the _exact_ list of properties that need to be
     * fetched, and haven't yet.
     *
     * However, you can also support the 'allprops' property here. In that
     * case, you should check for $propFind->isAllProps().
     *
     * @param string $path
     */
    public function propFind($path, PropFind $propFind);

    /**
     * Updates properties for a path.
     *
     * This method received a PropPatch object, which contains all the
     * information about the update.
     *
     * Usually you would want to call 'handleRemaining' on this object, to get;
     * a list of all properties that need to be stored.
     *
     * @param string $path
     */
    public function propPatch($path, PropPatch $propPatch);

    /**
     * This method is called after a node is deleted.
     *
     * This allows a backend to clean up all associated properties.
     *
     * The delete method will get called once for the deletion of an entire
     * tree.
     *
     * @param string $path
     */
    public function delete($path);

    /**
     * This method is called after a successful MOVE.
     *
     * This should be used to migrate all properties from one path to another.
     * Note that entire collections may be moved, so ensure that all properties
     * for children are also moved along.
     *
     * @param string $source
     * @param string $destination
     */
    public function move($source, $destination);
}
