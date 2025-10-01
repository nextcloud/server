<?php

declare(strict_types=1);

namespace Sabre\DAV\FS;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\Uri;

/**
 * Base node-class.
 *
 * The node class implements the method used by both the File and the Directory classes
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Node implements INode
{
    /**
     * The path to the current node.
     *
     * @var string
     */
    protected $path;

    /**
     * The overridden name of the node.
     *
     * @var string
     */
    protected $overrideName;

    /**
     * Sets up the node, expects a full path name.
     *
     * If $overrideName is set, this node shows up in the tree under a
     * different name. In this case setName() will be disabled.
     *
     * @param string $path
     * @param string $overrideName
     */
    public function __construct($path, $overrideName = null)
    {
        $this->path = $path;
        $this->overrideName = $overrideName;
    }

    /**
     * Returns the name of the node.
     *
     * @return string
     */
    public function getName()
    {
        if ($this->overrideName) {
            return $this->overrideName;
        }

        list(, $name) = Uri\split($this->path);

        return $name;
    }

    /**
     * Renames the node.
     *
     * @param string $name The new name
     */
    public function setName($name)
    {
        if ($this->overrideName) {
            throw new Forbidden('This node cannot be renamed');
        }

        list($parentPath) = Uri\split($this->path);
        list(, $newName) = Uri\split($name);

        $newPath = $parentPath.'/'.$newName;
        rename($this->path, $newPath);

        $this->path = $newPath;
    }

    /**
     * Returns the last modification time, as a unix timestamp.
     *
     * @return int
     */
    public function getLastModified()
    {
        return filemtime($this->path);
    }
}
