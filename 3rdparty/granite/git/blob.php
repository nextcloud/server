<?php
/**
 * Blob - provides a Git blob object
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
use \finfo as finfo;
/**
 * **Granite\Git\Blob** represents the raw content of an object in a Git repository,
 * typically a **file**. This class provides methods related to the handling of
 * blob content, mimetypes, sizes and write support.
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Blob
{

    /**
     * Stores the SHA-1 id of the object requested; accessed through the `sha()`
     * method where it is recalculated based on the blob content.
     */
    private $sha = null;
    /**
     * The raw binary string of the file contents.
     */
    private $content = "";
    /**
     * The path to the repository location.
     */
    private $path;

    /**
     * Fetches a raw Git object and parses the result. Throws an
     * InvalidArgumentException if the object is not of the correct type,
     * or cannot be found.
     *
     * @param string $path The path to the repository root.
     * @param string $sha  The SHA-1 id of the requested object, or `null` if
     *                     creating a new blob object.
     *
     * @throws InvalidArgumentException If the SHA-1 id provided is not a blob.
     */
    public function __construct($path, $sha = NULL)
    {
        $this->path = $path;
        if ($sha !== NULL) {
            $this->sha = $sha;
            $object = Raw::factory($path, $sha);

            if ($object->type() !== Raw::OBJ_BLOB) {
                throw new InvalidArgumentException(
                    "The object $sha is not a blob, type is {$object->type()}"
                );
            }

            $this->content = $object->content();
            unset($object);
       }
    }

    /**
     * Sets or returns the raw file content, depending whether the parameter is
     * provided.
     *
     * @param string $content The object content to set, or `null` if requesting the
     *                        current content.
     *
     * @return string The raw binary string of the file contents.
     */
    public function content($content = NULL)
    {
        if ($content == NULL) {
            return $this->content;
        }
        $this->content = $content;
    }

    /**
     * Returns the size of the file content in bytes, equivalent to
     * `strlen($blob->content())`.
     *
     * @return int The size of the object in bytes.
     */
    public function size()
    {
        return strlen($this->content);
    }

    /**
     * Updates and returns the SHA-1 id of the object, based on it's contents.
     *
     * @return int The SHA-1 id of the object.
     */
    public function sha()
    {
        $sha = hash_init('sha1');
        $header = 'blob ' . strlen($this->content) . "\0";
        hash_update($sha, $header);
        hash_update($sha, $this->content);
        $this->sha = hash_final($sha);
        return $this->sha;
    }

    /**
     * Returns the mimetype of the object, using `finfo()` to determine the mimetype
     * of the string.
     *
     * @return string The object mimetype.
     * @see http://php.net/manual/en/function.finfo-open.php
     */
    public function mimetype()
    {
        $finfo = new finfo(FILEINFO_MIME);
        return $finfo->buffer($this->content);
    }

    /**
     * Encode and compress the object content, saving it to a 'loose' file.
     *
     * @return boolean True on success, false on failure.
     */
    public function write()
    {
        $sha = $this->sha(TRUE);
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
        $data = 'blob ' . strlen($this->content) . "\0" . $this->content;
        $write = fwrite($loose, gzcompress($data));
        fclose($loose);

        return ($write !== FALSE);
    }

}
