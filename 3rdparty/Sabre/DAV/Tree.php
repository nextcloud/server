<?php

/**
 * Abstract tree object
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Tree {

    /**
     * This function must return an INode object for a path
     * If a Path doesn't exist, thrown a Exception_NotFound
     *
     * @param string $path
     * @throws Sabre_DAV_Exception_NotFound
     * @return Sabre_DAV_INode
     */
    abstract function getNodeForPath($path);

    /**
     * This function allows you to check if a node exists.
     *
     * Implementors of this class should override this method to make
     * it cheaper.
     *
     * @param string $path
     * @return bool
     */
    public function nodeExists($path) {

        try {

            $this->getNodeForPath($path);
            return true;

        } catch (Sabre_DAV_Exception_NotFound $e) {

            return false;

        }

    }

    /**
     * Copies a file from path to another
     *
     * @param string $sourcePath The source location
     * @param string $destinationPath The full destination path
     * @return void
     */
    public function copy($sourcePath, $destinationPath) {

        $sourceNode = $this->getNodeForPath($sourcePath);

        // grab the dirname and basename components
        list($destinationDir, $destinationName) = Sabre_DAV_URLUtil::splitPath($destinationPath);

        $destinationParent = $this->getNodeForPath($destinationDir);
        $this->copyNode($sourceNode,$destinationParent,$destinationName);

        $this->markDirty($destinationDir);

    }

    /**
     * Moves a file from one location to another
     *
     * @param string $sourcePath The path to the file which should be moved
     * @param string $destinationPath The full destination path, so not just the destination parent node
     * @return int
     */
    public function move($sourcePath, $destinationPath) {

        list($sourceDir, $sourceName) = Sabre_DAV_URLUtil::splitPath($sourcePath);
        list($destinationDir, $destinationName) = Sabre_DAV_URLUtil::splitPath($destinationPath);

        if ($sourceDir===$destinationDir) {
            $renameable = $this->getNodeForPath($sourcePath);
            $renameable->setName($destinationName);
        } else {
            $this->copy($sourcePath,$destinationPath);
            $this->getNodeForPath($sourcePath)->delete();
        }
        $this->markDirty($sourceDir);
        $this->markDirty($destinationDir);

    }

    /**
     * Deletes a node from the tree
     *
     * @param string $path
     * @return void
     */
    public function delete($path) {

        $node = $this->getNodeForPath($path);
        $node->delete();

        list($parent) = Sabre_DAV_URLUtil::splitPath($path);
        $this->markDirty($parent);

    }

    /**
     * Returns a list of childnodes for a given path.
     *
     * @param string $path
     * @return array
     */
    public function getChildren($path) {

        $node = $this->getNodeForPath($path);
        return $node->getChildren();

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


    }

    /**
     * copyNode
     *
     * @param Sabre_DAV_INode $source
     * @param Sabre_DAV_ICollection $destinationParent
     * @param string $destinationName
     * @return void
     */
    protected function copyNode(Sabre_DAV_INode $source,Sabre_DAV_ICollection $destinationParent,$destinationName = null) {

        if (!$destinationName) $destinationName = $source->getName();

        if ($source instanceof Sabre_DAV_IFile) {

            $data = $source->get();

            // If the body was a string, we need to convert it to a stream
            if (is_string($data)) {
                $stream = fopen('php://temp','r+');
                fwrite($stream,$data);
                rewind($stream);
                $data = $stream;
            }
            $destinationParent->createFile($destinationName,$data);
            $destination = $destinationParent->getChild($destinationName);

        } elseif ($source instanceof Sabre_DAV_ICollection) {

            $destinationParent->createDirectory($destinationName);

            $destination = $destinationParent->getChild($destinationName);
            foreach($source->getChildren() as $child) {

                $this->copyNode($child,$destination);

            }

        }
        if ($source instanceof Sabre_DAV_IProperties && $destination instanceof Sabre_DAV_IProperties) {

            $props = $source->getProperties(array());
            $destination->updateProperties($props);

        }

    }

}

