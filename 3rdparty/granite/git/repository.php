<?php
/**
 * Repository - provides a 'repository' object with a set of helper methods
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
use \InvalidArgumentException as InvalidArgumentException;
use \UnexpectedValueException as UnexpectedValueException;

/**
 * Repository represents a Git repository, providing a variety of methods for
 * fetching objects from SHA-1 ids or the tip of a branch with `head()`
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Repository
{

    /**
     * The path to the repository root
     */
    private $_path;
    /**
     * The indexed version of a commit, ready to write with `commit()`
     */
    private $idx_commit;
    /**
     * The indexed version of a tree, modified to with `add()` and `remove()`
     */
    private $idx_tree;

    /**
     * Sets the repository path
     *
     * @param string $path The path to the repository root (i.e. /repo/.git/)
     */
    public function __construct($path)
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException("Unable to find directory $path");
        } elseif (!is_readable($path)) {
            throw new InvalidArgumentException("Unable to read directory $path");
        } elseif (!is_dir($path . DIRECTORY_SEPARATOR . 'objects')
            || !is_dir($path . DIRECTORY_SEPARATOR . 'refs')
        ) {
            throw new UnexpectedValueException(
                "Invalid directory, could not find 'objects' or 'refs' in $path"
            );
        }

        $this->_path = $path;
        $this->idx_commit = $this->factory('commit');
        $this->idx_tree = $this->factory('tree');
    }

    /**
     * Returns an object from the Repository of the given type, with the given
     * SHA-1 id, or false if it cannot be found
     *
     * @param string $type The type (blob, commit, tag or tree) of object being
     * requested
     * @param string $sha  The SHA-1 id of the object (or the name of a tag)
     *
     * @return Blob|Commit|Tag|Tree
     */
    public function factory($type, $sha = null)
    {
        if (!in_array($type, array('blob', 'commit', 'tag', 'tree'))) {
            throw new InvalidArgumentException("Invalid type: $type");
        }

        if ($type == 'tag') {
            $sha = $this->_ref('tags' . DIRECTORY_SEPARATOR . $sha);
        }
        $type = 'Granite\\Git\\' . ucwords($type);

        return new $type($this->_path, $sha);
    }

    /**
     * Returns a Commit object representing the HEAD commit
     *
     * @param string $branch The branch name to lookup, defaults to 'master'
     *
     * @return Commit An object representing the HEAD commit
     */
    public function head($branch = 'master', $value = NULL)
    {
        if ($value == NULL)
            return $this->factory(
                'commit', $this->_ref('heads' . DIRECTORY_SEPARATOR . $branch)
            );

        file_put_contents(
            $this->_path . DIRECTORY_SEPARATOR
            . 'refs' . DIRECTORY_SEPARATOR
            . 'heads' . DIRECTORY_SEPARATOR . 'master',
            $value
        );
    }

    /**
     * Returns a string representing the repository's location, which may or may
     * not be initialised
     *
     * @return string A string representing the repository's location
     */
    public function path()
    {
        return $this->_path;
    }

    /**
     * Returns an array of the local branches under `refs/heads`
     *
     * @return array
     */
    public function tags()
    {
      return $this->_refs('tags');
    }

    /**
     * Returns an array of the local tags under `refs/tags`
     *
     * @return array
     */
    public function branches()
    {
      return $this->_refs('heads');
    }

    private function _refs($type)
    {
      $dir = $this->_path . 'refs' . DIRECTORY_SEPARATOR . $type;
      $refs = glob($dir . DIRECTORY_SEPARATOR . '*');
      foreach ($refs as &$ref) {
        $ref = basename($ref);
      }
      return $refs;
    }

    /**
     * Initialises a Git repository
     *
     * @return boolean Returns true on success, false on error
     */
    public static function init($path)
    {
      $path .= '/';
      if (!is_dir($path)) {
        mkdir($path);
      } elseif (is_dir($path . 'objects')) {
        return false;
      }

      mkdir($path . 'objects');
      mkdir($path . 'objects/info');
      mkdir($path . 'objects/pack');
      mkdir($path . 'refs');
      mkdir($path . 'refs/heads');
      mkdir($path . 'refs/tags');

      file_put_contents($path . 'HEAD', 'ref: refs/heads/master');

      return true;
    }

    /**
     * Writes the indexed commit to disk, with blobs added/removed via `add()` and
     * `rm()`
     *
     * @param string $message The commit message
     * @param string $author  The author name
     *
     * @return boolean True on success, or false on failure
     */
    public function commit($message, $author)
    {
        $user_string = $username . ' ' . time() . ' +0000';

        try {
            $parents = array($this->repo->head()->sha());
        } catch (InvalidArgumentException $e) {
            $parents = array();
        }

        $this->idx_commit->message($message);
        $this->idx_commit->author($user_string);
        $this->idx_commit->committer($user_string);
        $this->idx_commit->tree($this->idx_tree);
        $commit->parents($parents);

        $this->idx_tree->write();
        $this->idx_commit->write();

        $this->repo->head('master', $this->idx_commit->sha());

        $this->idx_commit = $this->factory('commit');
        $this->idx_tree = $this->factory('tree');
    }

    /**
     * Adds a file to the indexed commit, to be written to disk with `commit()`
     *
     * @param string           $filename The filename to save it under
     * @param Granite\Git\Blob $blob     The raw blob object to add to the tree
     */
    public function add($filename, Granite\Git\Blob $blob)
    {
        $blob->write();
        $nodes = $this->idx_tree->nodes();
        $nodes[$filename] = new Granite\Git\Tree\Node($filename, '100644', $blob->sha());
        $this->idx_tree->nodes($nodes);
    }

    /**
     * Removes a file from the indexed commit
     */
    public function rm($filename)
    {
        $nodes = $this->idx_tree->nodes();
        unset($nodes[$filename]);
        $this->idx_tree->nodes($nodes);
    }

    /**
     * Returns an SHA-1 id of the ref resource
     *
     * @param string $ref The ref name to lookup
     *
     * @return string An SHA-1 id of the ref resource
     */
    private function _ref($ref)
    {
        // All refs are stored in `.git/refs`
        $file = $this->_path . 'refs' . DIRECTORY_SEPARATOR . $ref;

        if (file_exists($file)) {
            return trim(file_get_contents($file));
        }

        $sha = $this->_packedRef($ref);

        if ($sha == false) {
            throw new InvalidArgumentException("The ref $ref could not be found");
        }

        return $sha;
    }

    /**
     * Returns an SHA-1 id of the ref resource, or false if it cannot be found
     *
     * @param string $ref The ref name to lookup
     *
     * @return string An SHA-1 id of the ref resource
     */
    private function _packedRef($ref)
    {
        $sha = false;
        if (file_exists($this->_path . 'packed-refs')) {
            $file = fopen($this->_path . 'packed-refs', 'r');

            while (($line = fgets($file)) !== false) {
                $info = explode(' ', $line);
                if (count($info) == 2
                    && trim($info[1]) == 'refs' . DIRECTORY_SEPARATOR . $ref
                ) {
                    $sha = trim($info[0]);
                    break;
                }
            }

            fclose($file);
        }

        return $sha;
    }

}
