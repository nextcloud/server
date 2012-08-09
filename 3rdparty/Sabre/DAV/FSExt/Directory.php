<?php

/**
 * Directory class
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_FSExt_Directory extends Sabre_DAV_FSExt_Node implements Sabre_DAV_ICollection, Sabre_DAV_IQuota {

    /**
     * Creates a new file in the directory
     *
     * Data will either be supplied as a stream resource, or in certain cases
     * as a string. Keep in mind that you may have to support either.
     *
     * After succesful creation of the file, you may choose to return the ETag
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
    public function createFile($name, $data = null) {

        // We're not allowing dots
        if ($name=='.' || $name=='..') throw new Sabre_DAV_Exception_Forbidden('Permission denied to . and ..');
        $newPath = $this->path . '/' . $name;
        file_put_contents($newPath,$data);

        return '"' . md5_file($newPath) . '"';

    }

    /**
     * Creates a new subdirectory
     *
     * @param string $name
     * @return void
     */
    public function createDirectory($name) {

        // We're not allowing dots
        if ($name=='.' || $name=='..') throw new Sabre_DAV_Exception_Forbidden('Permission denied to . and ..');
        $newPath = $this->path . '/' . $name;
        mkdir($newPath);

    }

    /**
     * Returns a specific child node, referenced by its name
     *
     * @param string $name
     * @throws Sabre_DAV_Exception_NotFound
     * @return Sabre_DAV_INode
     */
    public function getChild($name) {

        $path = $this->path . '/' . $name;

        if (!file_exists($path)) throw new Sabre_DAV_Exception_NotFound('File could not be located');
        if ($name=='.' || $name=='..') throw new Sabre_DAV_Exception_Forbidden('Permission denied to . and ..');

        if (is_dir($path)) {

            return new Sabre_DAV_FSExt_Directory($path);

        } else {

            return new Sabre_DAV_FSExt_File($path);

        }

    }

    /**
     * Checks if a child exists.
     *
     * @param string $name
     * @return bool
     */
    public function childExists($name) {

        if ($name=='.' || $name=='..')
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to . and ..');

        $path = $this->path . '/' . $name;
        return file_exists($path);

    }

    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    public function getChildren() {

        $nodes = array();
        foreach(scandir($this->path) as $node) if($node!='.' && $node!='..' && $node!='.sabredav') $nodes[] = $this->getChild($node);
        return $nodes;

    }

    /**
     * Deletes all files in this directory, and then itself
     *
     * @return bool
     */
    public function delete() {

        // Deleting all children
        foreach($this->getChildren() as $child) $child->delete();

        // Removing resource info, if its still around
        if (file_exists($this->path . '/.sabredav')) unlink($this->path . '/.sabredav');

        // Removing the directory itself
        rmdir($this->path);

        return parent::delete();

    }

    /**
     * Returns available diskspace information
     *
     * @return array
     */
    public function getQuotaInfo() {

        return array(
            disk_total_space($this->path)-disk_free_space($this->path),
            disk_free_space($this->path)
            );

    }

}

