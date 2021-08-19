<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * Node class.
 *
 * This is a helper class, that should aid in getting nodes setup.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Node implements INode
{
    /**
     * Returns the last modification time as a unix timestamp.
     *
     * If the information is not available, return null.
     *
     * @return int
     */
    public function getLastModified()
    {
        return null;
    }

    /**
     * Deletes the current node.
     *
     * @throws Exception\Forbidden
     */
    public function delete()
    {
        throw new Exception\Forbidden('Permission denied to delete node');
    }

    /**
     * Renames the node.
     *
     * @param string $name The new name
     *
     * @throws Exception\Forbidden
     */
    public function setName($name)
    {
        throw new Exception\Forbidden('Permission denied to rename file');
    }
}
