<?php
/**
 * Node - provides a tree node object for tree entries
 *
 * PHP version 5.3
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */

namespace Granite\Git\Tree;

/**
 * Node represents an entry in a Tree
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Node
{

    /**
     * Name of the file, directory or submodule
     */
    private $_name;
    /**
     * Mode of the object, in octal
     */
    private $_mode;
    /**
     * SHA-1 id of the tree
     */
    private $_sha;
    /**
     * Boolean value for whether the entry represents a directory
     */
    private $_is_dir;
    /**
     * Boolean value for whether the entry represents a submodule
     */
    private $_is_submodule;

    /**
     * Sets up a Node class with properties corresponding to the $mode parameter
     *
     * @param string $name The name of the object (file, directory or submodule name)
     * @param int    $mode The mode of the object, retrieved from the repository
     * @param string $sha  The SHA-1 id of the object
     */
    public function __construct($name, $mode, $sha)
    {
        $this->_name = $name;
        $this->_mode = intval($mode, 8);
        $this->_sha = $sha;

        $this->_is_dir = (bool) ($this->_mode & 0x4000);
        $this->_is_submodule = ($this->_mode == 0xE000);
    }

    /**
     * Returns a boolean value indicating whether the node is a directory
     *
     * @return boolean
     */
    public function isDirectory()
    {
        return $this->_is_dir;
    }

    /**
     * Returns a boolean value indicating whether the node is a submodule
     *
     * @return boolean
     */
    public function isSubmodule()
    {
        return $this->_is_submodule;
    }

    /**
     * Returns the object name
     *
     * @return string
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Returns the object's SHA-1 id
     *
     * @return string
     */
    public function sha()
    {
        return $this->_sha;
    }

    /**
     * Returns the octal value of the file mode
     *
     * @return int
     */
    public function mode()
    {
        return $this->_mode;
    }

    public function type()
    {
        if ($this->isDirectory()) {
            return 'tree';
        } elseif ($this->isSubmodule()) {
            return 'commit';
        } else {
            return 'blob';
        }
    }
}
