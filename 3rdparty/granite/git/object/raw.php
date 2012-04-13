<?php
/**
 * Raw - provides a raw Git object
 *
 * PHP version 5.3
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */

namespace Granite\Git\Object;
use \InvalidArgumentException as InvalidArgumentException;

/**
 * Raw represents a raw Git object, using Index to locate
 * packed objects.
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Raw
{
    /**
     * Integer values for Git objects
     * @see http://book.git-scm.com/7_the_packfile.html
     */
    const OBJ_COMMIT = 1;
    const OBJ_TREE = 2;
    const OBJ_BLOB = 3;
    const OBJ_TAG = 4;
    const OBJ_OFS_DELTA = 6;
    const OBJ_REF_DELTA = 7;

    /**
     * The SHA-1 id of the requested object
     */
    protected $sha;
    /**
     * The type of the requested object (see class constants)
     */
    protected $type;
    /**
     * The binary string content of the requested object
     */
    protected $content;

    /**
     * Returns an instance of a raw Git object
     *
     * @param string $path The path to the repository root
     * @param string $sha  The SHA-1 id of the requested object
     *
     * @return Packed|Loose
     */
    public static function factory($path, $sha)
    {
        $loose_path = $path
                      . 'objects/'
                      . substr($sha, 0, 2)
                      . '/'
                      . substr($sha, 2);
        if (file_exists($loose_path)) {
            return new Loose($path, $sha);
        } else {
            return self::_findPackedObject($path, $sha);
        }
    }

    /**
     * Returns the raw content of the Git object requested
     *
     * @return string Raw object content
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * Returns the size of the Git object
     *
     * @return int The size of the object in bytes
     */
    public function size()
    {
        return strlen($this->content);
    }

    /**
     * Returns the type of the object as either commit, tag, blob or tree
     *
     * @return string The object type
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Searches a packfile for the SHA id and reads the object from the packfile
     *
     * @param string $path The path to the repository
     * @param string $sha  The SHA-1 id of the object being requested
     *
     * @throws \InvalidArgumentException
     * @return array An array containing the type, size and object data
     */
    private static function _findPackedObject($path, $sha)
    {
        $packfiles = glob(
            $path
            . 'objects'
            . DIRECTORY_SEPARATOR
            . 'pack'
            . DIRECTORY_SEPARATOR
            . 'pack-*.pack'
        );

        $offset = false;
        foreach ($packfiles as $packfile) {
            $packname = substr(basename($packfile, '.pack'), 5);
            $idx = new Index($path, $packname);
            $offset = $idx->find($sha);

            if ($offset !== false) {
                break; // Found it
            }
        }

        if ($offset == false) {
            throw new InvalidArgumentException("Could not find packed object $sha");
        }

        $packname = $path
            . 'objects'
            . DIRECTORY_SEPARATOR
            . 'pack'
            . DIRECTORY_SEPARATOR
            . 'pack-' . $packname . '.pack';
        $object = new Packed($packname, $offset);

        return $object;
    }

}

?>
