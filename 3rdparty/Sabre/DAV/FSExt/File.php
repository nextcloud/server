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
class Sabre_DAV_FSExt_File extends Sabre_DAV_FSExt_Node implements Sabre_DAV_PartialUpdate_IFile {

    /**
     * Updates the data
     *
     * data is a readable stream resource.
     *
     * @param resource|string $data
     * @return string
     */
    public function put($data) {

        file_put_contents($this->path,$data);
        return '"' . md5_file($this->path) . '"';

    }

    /**
     * Updates the data at a given offset
     *
     * The data argument is a readable stream resource.
     * The offset argument is a 0-based offset where the data should be
     * written.
     *
     * param resource|string $data
     * @return void
     */
    public function putRange($data, $offset) {

        $f = fopen($this->path, 'c');
        fseek($f,$offset-1);
        if (is_string($data)) {
            fwrite($f, $data);
        } else {
            stream_copy_to_stream($data,$f);
        }
        fclose($f);
        return '"' . md5_file($this->path) . '"';

    }

    /**
     * Returns the data
     *
     * @return resource
     */
    public function get() {

        return fopen($this->path,'r');

    }

    /**
     * Delete the current file
     *
     * @return bool
     */
    public function delete() {

        unlink($this->path);
        return parent::delete();

    }

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return string|null
     */
    public function getETag() {

        return '"' . md5_file($this->path). '"';

    }

    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return string|null
     */
    public function getContentType() {

        return null;

    }

    /**
     * Returns the size of the file, in bytes
     *
     * @return int
     */
    public function getSize() {

        return filesize($this->path);

    }

}

