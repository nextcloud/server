<?php

declare(strict_types=1);

namespace Sabre\DAV\FS;

use Sabre\DAV;

/**
 * Directory class.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Directory extends Node implements DAV\ICollection, DAV\IQuota
{
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
        $newPath = $this->path.'/'.$name;
        file_put_contents($newPath, $data);
        clearstatcache(true, $newPath);
    }

    /**
     * Creates a new subdirectory.
     *
     * @param string $name
     */
    public function createDirectory($name)
    {
        $newPath = $this->path.'/'.$name;
        mkdir($newPath);
        clearstatcache(true, $newPath);
    }

    /**
     * Returns a specific child node, referenced by its name.
     *
     * This method must throw DAV\Exception\NotFound if the node does not
     * exist.
     *
     * @param string $name
     *
     * @throws DAV\Exception\NotFound
     *
     * @return DAV\INode
     */
    public function getChild($name)
    {
        $path = $this->path.'/'.$name;

        if (!file_exists($path)) {
            throw new DAV\Exception\NotFound('File with name '.$path.' could not be located');
        }
        if (is_dir($path)) {
            return new self($path);
        } else {
            return new File($path);
        }
    }

    /**
     * Returns an array with all the child nodes.
     *
     * @return DAV\INode[]
     */
    public function getChildren()
    {
        $nodes = [];
        $iterator = new \FilesystemIterator(
            $this->path,
            \FilesystemIterator::CURRENT_AS_SELF
          | \FilesystemIterator::SKIP_DOTS
        );
        foreach ($iterator as $entry) {
            $nodes[] = $this->getChild($entry->getFilename());
        }

        return $nodes;
    }

    /**
     * Checks if a child exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function childExists($name)
    {
        $path = $this->path.'/'.$name;

        return file_exists($path);
    }

    /**
     * Deletes all files in this directory, and then itself.
     */
    public function delete()
    {
        foreach ($this->getChildren() as $child) {
            $child->delete();
        }
        rmdir($this->path);
    }

    /**
     * Returns available diskspace information.
     *
     * @return array
     */
    public function getQuotaInfo()
    {
        $absolute = realpath($this->path);

        return [
            disk_total_space($absolute) - disk_free_space($absolute),
            disk_free_space($absolute),
        ];
    }
}
