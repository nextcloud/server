<?php

/**
 * This interface represents a file or leaf in the tree.
 *
 * The nature of a file is, as you might be aware of, that it doesn't contain sub-nodes and has contents
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_IFile extends Sabre_DAV_INode {

    /**
     * Updates the data 
     * 
     * The data argument is a readable stream resource.
     *
     * @param resource $data 
     * @return void 
     */
    function put($data);

    /**
     * Returns the data 
     * 
     * This method may either return a string or a readable stream resource
     *
     * @return mixed 
     */
    function get();

    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     * 
     * @return void
     */
    function getContentType();

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     *
     * Return null if the ETag can not effectively be determined
     * 
     * @return void
     */
    function getETag();

    /**
     * Returns the size of the node, in bytes 
     * 
     * @return int 
     */
    function getSize();

}

