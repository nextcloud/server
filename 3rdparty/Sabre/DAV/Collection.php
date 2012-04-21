<?php

/**
 * Collection class
 *
 * This is a helper class, that should aid in getting collections classes setup.
 * Most of its methods are implemented, and throw permission denied exceptions
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Collection extends Sabre_DAV_Node implements Sabre_DAV_ICollection {

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

        foreach($this->getChildren() as $child) {

            if ($child->getName()==$name) return $child;

        }
        throw new Sabre_DAV_Exception_NotFound('File not found: ' . $name);

    }

    /**
     * Checks is a child-node exists.
     *
     * It is generally a good idea to try and override this. Usually it can be optimized.
     *
     * @param string $name
     * @return bool
     */
    public function childExists($name) {

        try {

            $this->getChild($name);
            return true;

        } catch(Sabre_DAV_Exception_NotFound $e) {

            return false;

        }

    }

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

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename ' . $name . ')');

    }

    /**
     * Creates a new subdirectory
     *
     * @param string $name
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createDirectory($name) {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to create directory');

    }


}

