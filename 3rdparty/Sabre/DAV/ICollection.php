<?php

/**
 * The ICollection Interface
 *
 * This interface should be implemented by each class that represents a collection
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_ICollection extends Sabre_DAV_INode {

    /**
     * Creates a new file in the directory
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
     * @param string $name Name of the file
     * @param resource|string $data Initial payload
     * @return null|string
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
     * This method must throw Sabre_DAV_Exception_NotFound if the node does not
     * exist.
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
     * @param string $name
     * @return bool
     */
    function childExists($name);

}

