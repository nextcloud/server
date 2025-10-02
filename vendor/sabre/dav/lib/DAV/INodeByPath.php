<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * INodeByPath.
 *
 * This interface adds a tiny bit of functionality to collections.
 *
 * Getting a node that is deep in the tree normally requires going through each parent node
 * which can cause a significant performance overhead.
 *
 * Implementing this interface allows solving this overhead by directly jumping to the target node.
 *
 * @copyright Copyright (C) Robin Appelman (https://icewind.nl/)
 * @author Robin Appelman (https://icewind.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface INodeByPath
{
    /**
     * Returns the INode object for the requested path.
     *
     * In case where this collection can not retrieve the requested node
     * but also can not determine that the node does not exists,
     * null should be returned to signal that the caller should fallback
     * to walking the directory tree.
     *
     * @param string $path
     *
     * @return INode|null
     */
    public function getNodeForPath($path);
}
