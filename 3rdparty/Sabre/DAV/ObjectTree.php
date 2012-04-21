<?php

/**
 * ObjectTree class
 *
 * This implementation of the Tree class makes use of the INode, IFile and ICollection API's
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_ObjectTree extends Sabre_DAV_Tree {

    /**
     * The root node
     *
     * @var Sabre_DAV_ICollection
     */
    protected $rootNode;

    /**
     * This is the node cache. Accessed nodes are stored here
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Creates the object
     *
     * This method expects the rootObject to be passed as a parameter
     *
     * @param Sabre_DAV_ICollection $rootNode
     */
    public function __construct(Sabre_DAV_ICollection $rootNode) {

        $this->rootNode = $rootNode;

    }

    /**
     * Returns the INode object for the requested path
     *
     * @param string $path
     * @return Sabre_DAV_INode
     */
    public function getNodeForPath($path) {

        $path = trim($path,'/');
        if (isset($this->cache[$path])) return $this->cache[$path];

        //if (!$path || $path=='.') return $this->rootNode;
        $currentNode = $this->rootNode;

        // We're splitting up the path variable into folder/subfolder components and traverse to the correct node..
        foreach(explode('/',$path) as $pathPart) {

            // If this part of the path is just a dot, it actually means we can skip it
            if ($pathPart=='.' || $pathPart=='') continue;

            if (!($currentNode instanceof Sabre_DAV_ICollection))
                throw new Sabre_DAV_Exception_NotFound('Could not find node at path: ' . $path);

            $currentNode = $currentNode->getChild($pathPart);

        }

        $this->cache[$path] = $currentNode;
        return $currentNode;

    }

    /**
     * This function allows you to check if a node exists.
     *
     * @param string $path
     * @return bool
     */
    public function nodeExists($path) {

        try {

            // The root always exists
            if ($path==='') return true;

            list($parent, $base) = Sabre_DAV_URLUtil::splitPath($path);

            $parentNode = $this->getNodeForPath($parent);
            if (!$parentNode instanceof Sabre_DAV_ICollection) return false;
            return $parentNode->childExists($base);

        } catch (Sabre_DAV_Exception_NotFound $e) {

            return false;

        }

    }

    /**
     * Returns a list of childnodes for a given path.
     *
     * @param string $path
     * @return array
     */
    public function getChildren($path) {

        $node = $this->getNodeForPath($path);
        $children = $node->getChildren();
        foreach($children as $child) {

            $this->cache[trim($path,'/') . '/' . $child->getName()] = $child;

        }
        return $children;

    }

    /**
     * This method is called with every tree update
     *
     * Examples of tree updates are:
     *   * node deletions
     *   * node creations
     *   * copy
     *   * move
     *   * renaming nodes
     *
     * If Tree classes implement a form of caching, this will allow
     * them to make sure caches will be expired.
     *
     * If a path is passed, it is assumed that the entire subtree is dirty
     *
     * @param string $path
     * @return void
     */
    public function markDirty($path) {

        // We don't care enough about sub-paths
        // flushing the entire cache
        $path = trim($path,'/');
        foreach($this->cache as $nodePath=>$node) {
            if ($nodePath == $path || strpos($nodePath,$path.'/')===0)
                unset($this->cache[$nodePath]);

        }

    }

}

