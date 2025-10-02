<?php

declare(strict_types=1);

namespace Sabre\DAV;

use InvalidArgumentException;

/**
 * SimpleCollection.
 *
 * The SimpleCollection is used to quickly setup static directory structures.
 * Just create the object with a proper name, and add children to use it.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SimpleCollection extends Collection
{
    /**
     * List of childnodes.
     *
     * @var INode[]
     */
    protected $children = [];

    /**
     * Name of this resource.
     *
     * @var string
     */
    protected $name;

    /**
     * Creates this node.
     *
     * The name of the node must be passed, child nodes can also be passed.
     * This nodes must be instances of INode
     *
     * @param string  $name
     * @param INode[] $children
     */
    public function __construct($name, array $children = [])
    {
        $this->name = $name;
        foreach ($children as $key => $child) {
            if (is_string($child)) {
                $child = new SimpleFile($key, $child);
            } elseif (is_array($child)) {
                $child = new self($key, $child);
            } elseif (!$child instanceof INode) {
                throw new InvalidArgumentException('Children must be specified as strings, arrays or instances of Sabre\DAV\INode');
            }
            $this->addChild($child);
        }
    }

    /**
     * Adds a new childnode to this collection.
     */
    public function addChild(INode $child)
    {
        $this->children[$child->getName()] = $child;
    }

    /**
     * Returns the name of the collection.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns a child object, by its name.
     *
     * This method makes use of the getChildren method to grab all the child nodes, and compares the name.
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
        if (isset($this->children[$name])) {
            return $this->children[$name];
        }
        throw new Exception\NotFound('File not found: '.$name.' in \''.$this->getName().'\'');
    }

    /**
     * Returns a list of children for this collection.
     *
     * @return INode[]
     */
    public function getChildren()
    {
        return array_values($this->children);
    }
}
