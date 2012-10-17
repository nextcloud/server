<?php

/**
 * File class
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_FS_File extends Sabre_DAV_FS_Node implements Sabre_DAV_IFile {

    /**
     * Updates the data
     *
     * @param resource $data
     * @return void
     */
    public function put($data) {

        file_put_contents($this->path,$data);

    }

    /**
     * Returns the data
     *
     * @return string
     */
    public function get() {

        return fopen($this->path,'r');

    }

    /**
     * Delete the current file
     *
     * @return void
     */
    public function delete() {

        unlink($this->path);

    }

    /**
     * Returns the size of the node, in bytes
     *
     * @return int
     */
    public function getSize() {

        return filesize($this->path);

    }

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return mixed
     */
    public function getETag() {

        return null;

    }

    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return mixed
     */
    public function getContentType() {

        return null;

    }

}

