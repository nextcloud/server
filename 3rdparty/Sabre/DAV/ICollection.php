<?php

/**
 * The ICollection Interface
 *
 * This interface should be implemented by each class that represents a collection 
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_ICollection extends Sabre_DAV_INode {

    /**
     * Creates a new file in the directory 
     * 
     * data is a readable stream resource
     *
     * @param string $name Name of the file 
     * @param resource $data Initial payload 
     * @return void
     */
    function createFile($name, $data = null);

    /**
     * Creates a new subdirectory 
     * 
     * @param string $name 
     * @return void
     */
    function createDirectory($name);

    /**
     * Returns a specific child node, referenced by its name 
     * 
     * @param string $name 
     * @return Sabre_DAV_INode 
     */
    function getChild($name);

    /**
     * Returns an array with all the child nodes 
     * 
     * @return Sabre_DAV_INode[] 
     */
    function getChildren();

    /**
     * Checks if a child-node with the specified name exists 
     * 
     * @return bool 
     */
    function childExists($name);

}

