<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * By implementing this interface, a collection can effectively say "other
 * nodes may be moved into this collection".
 *
 * The benefit of this, is that sabre/dav will by default perform a move, by
 * transferring an entire directory tree, copying every collection, and deleting
 * every item.
 *
 * If a backend supports a better optimized move operation, this can trigger
 * some huge speed gains.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IMoveTarget extends ICollection
{
    /**
     * Moves a node into this collection.
     *
     * It is up to the implementors to:
     *   1. Create the new resource.
     *   2. Remove the old resource.
     *   3. Transfer any properties or other data.
     *
     * Generally you should make very sure that your collection can easily move
     * the move.
     *
     * If you don't, just return false, which will trigger sabre/dav to handle
     * the move itself. If you return true from this function, the assumption
     * is that the move was successful.
     *
     * @param string $targetName new local file/collection name
     * @param string $sourcePath Full path to source node
     * @param INode  $sourceNode Source node itself
     *
     * @return bool
     */
    public function moveInto($targetName, $sourcePath, INode $sourceNode);
}
