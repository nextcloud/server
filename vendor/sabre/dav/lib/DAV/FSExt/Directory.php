<?php

declare(strict_types=1);

namespace Sabre\DAV\FSExt;

use Sabre\DAV;
use Sabre\DAV\FS\Node;

/**
 * Directory class.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Directory extends Node implements DAV\ICollection, DAV\IQuota, DAV\IMoveTarget
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
        // We're not allowing dots
        if ('.' == $name || '..' == $name) {
            throw new DAV\Exception\Forbidden('Permission denied to . and ..');
        }
        $newPath = $this->path.'/'.$name;
        file_put_contents($newPath, $data);
        clearstatcache(true, $newPath);

        return '"'.sha1(
            fileinode($newPath).
            filesize($newPath).
            filemtime($newPath)
        ).'"';
    }

    /**
     * Creates a new subdirectory.
     *
     * @param string $name
     */
    public function createDirectory($name)
    {
        // We're not allowing dots
        if ('.' == $name || '..' == $name) {
            throw new DAV\Exception\Forbidden('Permission denied to . and ..');
        }
        $newPath = $this->path.'/'.$name;
        mkdir($newPath);
        clearstatcache(true, $newPath);
    }

    /**
     * Returns a specific child node, referenced by its name.
     *
     * This method must throw Sabre\DAV\Exception\NotFound if the node does not
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
            throw new DAV\Exception\NotFound('File could not be located');
        }
        if ('.' == $name || '..' == $name) {
            throw new DAV\Exception\Forbidden('Permission denied to . and ..');
        }
        if (is_dir($path)) {
            return new self($path);
        } else {
            return new File($path);
        }
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
        if ('.' == $name || '..' == $name) {
            throw new DAV\Exception\Forbidden('Permission denied to . and ..');
        }
        $path = $this->path.'/'.$name;

        return file_exists($path);
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
     * Deletes all files in this directory, and then itself.
     *
     * @return bool
     */
    public function delete()
    {
        // Deleting all children
        foreach ($this->getChildren() as $child) {
            $child->delete();
        }

        // Removing the directory itself
        rmdir($this->path);

        return true;
    }

    /**
     * Returns available diskspace information.
     *
     * @return array
     */
    public function getQuotaInfo()
    {
        $total = disk_total_space(realpath($this->path));
        $free = disk_free_space(realpath($this->path));

        return [
            $total - $free,
            $free,
        ];
    }

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
     * @param string    $targetName new local file/collection name
     * @param string    $sourcePath Full path to source node
     * @param DAV\INode $sourceNode Source node itself
     *
     * @return bool
     */
    public function moveInto($targetName, $sourcePath, DAV\INode $sourceNode)
    {
        // We only support FSExt\Directory or FSExt\File objects, so
        // anything else we want to quickly reject.
        if (!$sourceNode instanceof self && !$sourceNode instanceof File) {
            return false;
        }

        // PHP allows us to access protected properties from other objects, as
        // long as they are defined in a class that has a shared inheritance
        // with the current class.
        return rename($sourceNode->path, $this->path.'/'.$targetName);
    }
}
