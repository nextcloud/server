<?php

/**
 * SimpleCollection
 *
 * The SimpleCollection is used to quickly setup static directory structures.
 * Just create the object with a proper name, and add children to use it.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_SimpleCollection extends Sabre_DAV_Collection {

    /**
     * List of childnodes
     *
     * @var array
     */
    protected $children = array();

    /**
     * Name of this resource
     *
     * @var string
     */
    protected $name;

    /**
     * Creates this node
     *
     * The name of the node must be passed, child nodes can also be bassed.
     * This nodes must be instances of Sabre_DAV_INode
     *
     * @param string $name
     * @param array $children
     */
    public function __construct($name,array $children = array()) {

        $this->name = $name;
        foreach($children as $child) {

            if (!($child instanceof Sabre_DAV_INode)) throw new Sabre_DAV_Exception('Only instances of Sabre_DAV_INode are allowed to be passed in the children argument');
            $this->addChild($child);

        }

    }

    /**
     * Adds a new childnode to this collection
     *
     * @param Sabre_DAV_INode $child
     * @return void
     */
    public function addChild(Sabre_DAV_INode $child) {

        $this->children[$child->getName()] = $child;

    }

    /**
     * Returns the name of the collection
     *
     * @return string
     */
    public function getName() {

        return $this->name;

    }

    /**
     * Returns a child object, by its name.
     *
     * This method makes use of the getChildren method to grab all the child nodes, and compares the name.
     * Generally its wise to override this, as this can usually be optimized
     *
     * @param string $name
     * @throws Sabre_DAV_Exception_NotFound
     * @return Sabre_DAV_INode
     */
    public function getChild($name) {

        if (isset($this->children[$name])) return $this->children[$name];
        throw new Sabre_DAV_Exception_NotFound('File not found: ' . $name . ' in \'' . $this->getName() . '\'');

    }

    /**
     * Returns a list of children for this collection
     *
     * @return array
     */
    public function getChildren() {

        return array_values($this->children);

    }


}

