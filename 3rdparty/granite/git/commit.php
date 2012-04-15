<?php
/**
 * Commit - provides a 'commit' object
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
use \Granite\Git\Object\Raw as Raw;
use \InvalidArgumentException as InvalidArgumentException;

/**
 * Commit represents a full commit object
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Commit
{

    /**
     * The path to the repository root
     */
    private $path;
    /**
     * The SHA-1 id of the requested commit
     */
    private $sha;
    /**
     * The size of the commit in bytes
     */
    private $size;
    /**
     * The commit message
     */
    private $message;
    /**
     * The full committer string
     */
    private $committer;
    /**
     * The full author string
     */
    private $author;
    /**
     * The SHA-1 ids of the parent commits
     */
    private $parents = array();

    /**
     * Fetches a raw Git object and parses the result. Throws an
     * InvalidArgumentException if the object is not of the correct type,
     * or cannot be found.
     *
     * @param string $path The path to the repository root
     * @param string $sha  The SHA-1 id of the requested object
     *
     * @throws InvalidArgumentException
     */
    public function __construct($path, $sha = NULL)
    {
        $this->path = $path;
        if ($sha !== NULL) {
            $this->sha = $sha;
            $object = Raw::factory($path, $sha);
            $this->size = $object->size();

            if ($object->type() !== Raw::OBJ_COMMIT) {
                throw new InvalidArgumentException(
                    "The object $sha is not a commit, type is " . $object->type()
                );
            }

            // Parse headers and commit message (delimited with "\n\n")
            list($headers, $this->message) = explode("\n\n", $object->content(), 2);
            $headers = explode("\n", $headers);

            foreach ($headers as $header) {
                list($header, $value) = explode(' ', $header, 2);
                if ($header == 'parent') {
                    $this->parents[] = $value;
                } else {
                    $this->$header = $value;
                }
            }

            $this->tree = new Tree($this->path, $this->tree);
        }
    }

    /**
     * Returns the message stored in the commit
     *
     * @return string The commit message
     */
    public function message($message = NULL)
    {
        if ($message !== NULL) {
            $this->message = $message;
            return $this;
        }
        return $this->message;
    }

    /**
     * Returns the commiter string
     *
     * @return string The committer string
     */
    public function committer($committer = NULL)
    {
        if ($committer !== NULL) {
            $this->committer = $committer;
            return $this;
        }
        return $this->committer;
    }

    /**
     * Returns the author string
     *
     * @return string The author string
     */
    public function author($author = NULL)
    {
        if ($author !== NULL) {
            $this->author = $author;
            return $this;
        }
        return $this->author;
    }

    /**
     * Returns the parents of the commit, or an empty array if none
     *
     * @return array The parents of the commit
     */
    public function parents($parents = NULL)
    {
        if ($parents !== NULL) {
            $this->parents = $parents;
            return $this;
        }
        return $this->parents;
    }

    /**
     * Returns a tree object associated with the commit
     *
     * @return Tree
     */
    public function tree(Tree $tree = NULL)
    {
        if ($tree !== NULL) {
            $this->tree = $tree;
            return $this;
        }
        return $this->tree;
    }

    /**
     * Returns the size of the commit in bytes (Git header + data)
     *
     * @return int
     */
    public function size()
    {
        return $this->size;
    }

    /**
     * Returns the size of the commit in bytes (Git header + data)
     *
     * @return int
     */
    public function sha()
    {
        $this->sha = hash('sha1', $this->_raw());
        return $this->sha;
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
        $write = fwrite($loose, gzcompress($data));
        fclose($loose);

        return ($write !== FALSE);
    }

    public function _raw()
    {
        $data = 'tree ' . $this->tree->sha() . "\n";
        foreach ($this->parents as $parent)
        {
            $data .= "parent $parent\n";
        }
        $data .= 'author ' . $this->author . "\n";
        $data .= 'committer ' . $this->committer . "\n\n";
        $data .= $this->message;

        $data = 'commit ' . strlen($data) . "\0" . $data;
        return $data;
    }

}
