<?php
/**
 * Tree - provides a 'tree' object
 *
 * PHP version 5.3
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */

namespace Granite\Git;
use \Granite\Git\Tree\Node as Node;

/**
 * Tree represents a full tree object, with nodes pointing to other tree objects
 * and file blobs
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Tree
{

    /**
     * The SHA-1 id of the requested tree
     */
    private $sha;
    /**
     * The nodes/entries for the requested tree
     */
    private $nodes = array();
    /**
     * The path to the repository
     */
    private $path;

    /**
     * Reads a tree object by fetching the raw object
     *
     * @param string $path The path to the repository root
     * @param string $sha  The SHA-1 id of the requested object
     */
    public function __construct($path, $sha = NULL,  $dbg = FALSE)
    {
        $this->path = $path;
        if ($sha !== NULL) {
            $object = Object\Raw::factory($path, $sha);
            $this->sha = $sha;

            if ($object->type() !== Object\Raw::OBJ_TREE) {
                throw new \InvalidArgumentException(
                    "The object $sha is not a tree, type is " . $object->type()
                );
            }

            $content = $object->content();
            file_put_contents('/tmp/tree_from_real_repo'.time(), $content);
            $nodes = array();

            for ($i = 0; $i < strlen($content); $i = $data_start + 21) {
                $data_start = strpos($content, "\0", $i);
                $info = substr($content, $i, $data_start-$i);
                list($mode, $name) = explode(' ', $info, 2);
                // Read the object SHA-1 id
                $sha = bin2hex(substr($content, $data_start + 1, 20));

                $this->nodes[$name] = new Node($name, $mode, $sha);
            }
        }
    }

    /**
     * Returns an array of Tree and Granite\Git\Blob objects,
     * representing subdirectories and files
     *
     * @return array Array of Tree and Granite\Git\Blob objects
     */
    public function nodes($nodes = null)
    {
        if ($nodes == null) {
            return $this->nodes;
        }
        $this->nodes = $nodes;
    }

    /**
     * Adds a blob or a tree to the list of nodes
     *
     * @param string $name The basename (filename) of the blob or tree
     * @param string $mode The mode of the blob or tree (see above)
     * @param string $sha  The SHA-1 id of the blob or tree to add
     */
    public function add($name, $mode, $sha)
    {
        $this->nodes[$name] = new Node($name, $mode, $sha);
        uasort($this->nodes, array($this, '_sort'));
    }

    public function write()
    {
        $sha = $this->sha();
        $path = $this->path
            . 'objects'
            . DIRECTORY_SEPARATOR
            . substr($sha, 0, 2)
            . DIRECTORY_SEPARATOR
            . substr($sha, 2);
        // FIXME: currently writes loose objects only
        if (file_exists($path)) {
            return FALSE;
        }

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, TRUE);
        }

        $loose = fopen($path, 'wb');
        $data = $this->_raw();
        $data = 'tree ' . strlen($data) . "\0" . $data;
        $write = fwrite($loose, gzcompress($data));
        fclose($loose);

        return ($write !== FALSE);
    }

    /**
     * Returns the SHA-1 id of the Tree
     *
     * @return string SHA-1 id of the Tree
     */
    public function sha()
    {
        $data = $this->_raw();
        $raw = 'tree ' . strlen($data) . "\0" . $data;
        $this->sha = hash('sha1', $raw);
        return $this->sha;
    }

    /**
     * Generates the raw object content to be saved to disk
     */
    public function _raw()
    {
        uasort($this->nodes, array($this, '_sort'));
        $data = '';
        foreach ($this->nodes as $node)
        {
            $data .= base_convert($node->mode(), 10, 8) . ' ' . $node->name() . "\0";
            $data .= pack('H40', $node->sha());
        }
        file_put_contents('/tmp/tree_made'.time(), $data);
        return $data;
    }

    /**
     * Sorts the node entries in a tree, general sort method adapted from original
     * Git C code (see @see tag below).
     *
     * @return 1, 0 or -1 if the first entry is greater than, the same as, or less
     *         than the second, respectively.
     * @see https://github.com/gitster/git/blob/master/read-cache.c Around line 352,
     *      the `base_name_compare` function
     */
    public function _sort(&$a, &$b)
    {
        $length = strlen($a->name()) < strlen($b->name()) ? strlen($a->name()) : strlen($b->name());

        $cmp = strncmp($a->name(), $b->name(), $length);
        if ($cmp) {
            return $cmp;
        }

        $suffix1 = $a->name();
        $suffix1 = (strlen($suffix1) > $length) ? $suffix1{$length} : FALSE;
        $suffix2 = $b->name();
        $suffix2 = (strlen($suffix2) > $length) ? $suffix2{$length} : FALSE;
        if (!$suffix1 && $a->isDirectory()) {
            $suffix1 = '/';
        }
        if (!$suffix2 && $b->isDirectory()) {
            $suffix2 = '/';
        }
        if ($suffix1 < $suffix2) {
            return -1;
        } elseif ($suffix1  > $suffix2) {
            return 1;
        }

        return 0;
    }

}
