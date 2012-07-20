<?php

/**
 * Sabre_DAV_Tree_Filesystem
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Tree_Filesystem extends Sabre_DAV_Tree {

    /**
     * Base url on the filesystem.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Creates this tree
     *
     * Supply the path you'd like to share.
     *
     * @param string $basePath
     */
    public function __construct($basePath) {

        $this->basePath = $basePath;

    }

    /**
     * Returns a new node for the given path
     *
     * @param string $path
     * @return Sabre_DAV_FS_Node
     */
    public function getNodeForPath($path) {

        $realPath = $this->getRealPath($path);
        if (!file_exists($realPath)) throw new Sabre_DAV_Exception_NotFound('File at location ' . $realPath . ' not found');
        if (is_dir($realPath)) {
            return new Sabre_DAV_FS_Directory($realPath);
        } else {
            return new Sabre_DAV_FS_File($realPath);
        }

    }

    /**
     * Returns the real filesystem path for a webdav url.
     *
     * @param string $publicPath
     * @return string
     */
    protected function getRealPath($publicPath) {

        return rtrim($this->basePath,'/') . '/' . trim($publicPath,'/');

    }

    /**
     * Copies a file or directory.
     *
     * This method must work recursively and delete the destination
     * if it exists
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    public function copy($source,$destination) {

        $source = $this->getRealPath($source);
        $destination = $this->getRealPath($destination);
        $this->realCopy($source,$destination);

    }

    /**
     * Used by self::copy
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    protected function realCopy($source,$destination) {

        if (is_file($source)) {
            copy($source,$destination);
        } else {
            mkdir($destination);
            foreach(scandir($source) as $subnode) {

                if ($subnode=='.' || $subnode=='..') continue;
                $this->realCopy($source.'/'.$subnode,$destination.'/'.$subnode);

            }
        }

    }

    /**
     * Moves a file or directory recursively.
     *
     * If the destination exists, delete it first.
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    public function move($source,$destination) {

        $source = $this->getRealPath($source);
        $destination = $this->getRealPath($destination);
        rename($source,$destination);

    }

}

