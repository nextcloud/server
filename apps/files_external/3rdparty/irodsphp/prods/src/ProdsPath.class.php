<?php
/**
 * ProdsPath class file.
 * @author Sifang Lu <sifang@sdsc.edu>
 * @copyright Copyright &copy; 2007, TBD
 * @package Prods
 */

require_once("autoload.inc.php");

require_once(CLASS_DIR . "/ProdsConfig.inc.php");

/**
 * ProdsPath class. This class is a abastract class for objects that can be represented as a path, such as file or directory
 * @package Prods
 */
abstract class ProdsPath
{
    /**
     * string path
     * @var string
     */
    public $path_str;

    public $account;

    protected $path_exists;

    protected $parent_path;
    protected $name;

    /**
     * Default Constructor. Because this class is abstract, this constructor should not be called directly.
     *
     * @param RODSAccount account iRODS account used for connection
     * @param string $path_str the path of this dir
     * @return ProdsPath a new ProdsPath
     */
    public function __construct(RODSAccount &$account, $path_str)
    {
        $this->account = $account;

        // strip the tailing "/"
        while ((strlen($path_str) > 1) && ($path_str{strlen($path_str) - 1} == '/')) {
            $path_str = substr($path_str, 0, strlen($path_str) - 1);
        }
        // remove duplicate '/' characters
        $path_str = str_replace('//', '/', $path_str);
        $this->path_str = $path_str;
        if ($path_str == '/') {
            $this->parent_path = null;
        } else {
            $this->parent_path = dirname($this->path_str);
        }
        $this->name = basename($this->path_str);
    }

    public function __toString()
    {
        return $this->account . $this->path_str;
    }

    /**
     * Whether this path (dir or file) exists on the server.
     * @return boolean
     */
    public function exists()
    {
        if (isset($this->path_exists))
            return $this->path_exists;

        else {
            $this->verify();
            return $this->path_exists;
        }
    }

    /**
     * Verify if a path exist with server. This function shouldn't be called directly, use {@link exists}
     */
    abstract protected function verify();

    /**
     * Get meta data of this path (file or dir).
     * @return array array of RODSMeta.
     */
    //public function getMeta()
    public function getMeta($get_cb = array('RODSConnManager', 'getConn'),
                            $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        if ($this instanceof ProdsFile)
            $type = 'd';
        else
            if ($this instanceof ProdsDir)
                $type = 'c';
            else
                throw new RODSException("Unsupported data type:" . get_class($this),
                    "PERR_INTERNAL_ERR");
        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $meta_array = $conn->getMeta($type, $this->path_str);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
        return $meta_array;
    }

    /**
     * update metadata to this path (file or dir)
     */
    public function updateMeta(RODSMeta $meta_old, RODSMeta $meta_new)
    {
        $this->rmMeta($meta_old);
        $this->addMeta($meta_new);
    }

    /**
     * Add metadata to this path (file or dir)
     */
    // public function addMeta(RODSMeta $meta)
    public function addMeta(RODSMeta $meta,
                            $get_cb = array('RODSConnManager', 'getConn'),
                            $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        if ($this instanceof ProdsFile)
            $type = 'd';
        else
            if ($this instanceof ProdsDir)
                $type = 'c';
            else
                throw new RODSException("Unsupported data type:" . get_class($this),
                    "PERR_INTERNAL_ERR");

        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $conn->addMeta($type, $this->path_str, $meta);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
    }

    /**
     * remove metadata to this path (file or dir)
     */
    // public function rmMeta(RODSMeta $meta)
    public function rmMeta(RODSMeta $meta,
                           $get_cb = array('RODSConnManager', 'getConn'),
                           $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        if ($this instanceof ProdsFile)
            $type = 'd';
        else
            if ($this instanceof ProdsDir)
                $type = 'c';
            else
                throw new RODSException("Unsupported data type:" . get_class($this),
                    "PERR_INTERNAL_ERR");

        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $conn->rmMeta($type, $this->path_str, $meta);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
    }

    /**
     * remove metadata of this path (file or dir) by id
     * @param integer metaid id of the metadata entry
     */
    // public function rmMetaByID ($metaid)
    public function rmMetaByID($metaid,
                               $get_cb = array('RODSConnManager', 'getConn'),
                               $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        if ($this instanceof ProdsFile)
            $type = 'd';
        else
            if ($this instanceof ProdsDir)
                $type = 'c';
            else
                throw new RODSException("Unsupported data type:" . get_class($this),
                    "PERR_INTERNAL_ERR");

        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $conn->rmMetaByID($type, $this->path_str, $metaid);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
    }

    /**
     * copy meta data from this path (file or dir) to $dest path
     */
    // public function cpMeta(ProdsPath $dest)
    public function cpMeta(ProdsPath $dest,
                           $get_cb = array('RODSConnManager', 'getConn'),
                           $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        if ($this instanceof ProdsFile)
            $type_src = 'd';
        else
            if ($this instanceof ProdsDir)
                $type_src = 'c';
            else
                throw new RODSException("Unsupported data type:" . get_class($this),
                    "PERR_INTERNAL_ERR");

        if ($dest instanceof ProdsFile)
            $type_dest = 'd';
        else
            if ($dest instanceof ProdsDir)
                $type_dest = 'c';
            else
                throw new RODSException("Unsupported data type:" . get_class($this),
                    "PERR_INTERNAL_ERR");

        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $conn->cpMeta($type_src, $type_dest, $this->path_str, $dest->path_str);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
    }

    /**
     * rename this path (file of dir)
     * @param string $new_path_str new path string to be renamed to.
     */
    // public function rename($new_path_str)
    public function rename($new_path_str,
                           $get_cb = array('RODSConnManager', 'getConn'),
                           $rel_cb = array('RODSConnManager', 'releaseConn'))
    {
        if ($this instanceof ProdsFile)
            $type = 0;
        else
            $type = 1;
        //$conn = RODSConnManager::getConn($this->account);
        $conn = call_user_func_array($get_cb, array(&$this->account));
        $conn->rename($this->path_str, $new_path_str, $type);
        //RODSConnManager::releaseConn($conn);
        call_user_func($rel_cb, $conn);
        $this->path_str = $new_path_str;
        $this->parent_path = dirname($this->path_str);
        $this->name = basename($this->path_str);
    }

    /**
     * Get name of this path. note that this is not the full path. for instance if path is "/foo/bar", the name is "bar"
     * @return string name of the path.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get string form of this path. note that this is the full path.
     * @return string form of the path.
     */
    public function getPath()
    {
        return $this->path_str;
    }

    /**
     * Get parent's path of this path.
     * @return string parent's path.
     */
    public function getParentPath()
    {
        return $this->parent_path;
    }

    /**
     * Get URI of this path.
     * @return string this path's URI.
     */
    public function toURI()
    {
        return $this->account->toURI() . $this->path_str;
    }

}
