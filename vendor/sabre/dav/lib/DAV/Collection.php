<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * Collection class.
 *
 * This is a helper class, that should aid in getting collections classes setup.
 * Most of its methods are implemented, and throw permission denied exceptions
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Collection extends Node implements ICollection
{
    /**
     * Returns a child object, by its name.
     *
     * This method makes use of the getChildren method to grab all the child
     * nodes, and compares the name.
     * Generally its wise to override this, as this can usually be optimized
     *
     * This method must throw Sabre\DAV\Exception\NotFound if the node does not
     * exist.
     *
     * @param string $name
     *
     * @throws Exception\NotFound
     *
     * @return INode
     */
    public function getChild($name)
    {
        foreach ($this->getChildren() as $child) {
            if ($child->getName() === $name) {
                return $child;
            }
        }
        throw new Exception\NotFound('File not found: '.$name);
    }

    /**
     * Checks is a child-node exists.
     *
     * It is generally a good idea to try and override this. Usually it can be optimized.
     *
     * @param string $name
     *
     * @return bool
     */
    public function childExists($name)
    {
        try {
            $this->getChild($name);

            return true;
        } catch (Exception\NotFound $e) {
            return false;
        }
    }

    /**
     * Creates a new file in the directory.
     *
     * Data will either be supplied as a stream resource, or in certain cases
     * as a string. Keep in mind that you may have to support either.
     *
     * After successful creation of the file, you may choose to return the ETag
     * of the new file here.
     *
     * The returned ETag must be surrounded by double-quotes (The quotes should
     * be part of the actual string).
     *
     * If you cannot accurately determine the ETag, you should not return it.
     * If you don't store the file exactly as-is (you're transforming it
     * somehow) you should also not return an ETag.
     *
     * This means that if a subsequent GET to this new file does not exactly
     * return the same contents of what was submitted here, you are strongly
     * recommended to omit the ETag.
     *
     * @param string          $name Name of the file
     * @param resource|string $data Initial payload
     *
     * @return string|null
     */
    public function createFile($name, $data = null)
    {
        throw new Exception\Forbidden('Permission denied to create file (filename '.$name.')');
    }

    /**
     * Creates a new subdirectory.
     *
     * @param string $name
     *
     * @throws Exception\Forbidden
     */
    public function createDirectory($name)
    {
        throw new Exception\Forbidden('Permission denied to create directory');
    }
}
