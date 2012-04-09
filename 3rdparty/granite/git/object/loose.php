<?php
/**
 * Loose - provides a 'loose object' object
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
use \UnexpectedValueException as UnexpectedValueException;

/**
 * Loose represents a loose object in the Git repository
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Loose extends Raw
{

    /**
     * Reads an object from a loose object file based on the SHA-1 id
     *
     * @param string $path The path to the repository root
     * @param string $sha  The SHA-1 id of the requested object
     *
     * @throws UnexpectedValueException If the type is not 'commit', 'tree',
     * 'tag' or 'blob'
     */
    public function __construct($path, $sha)
    {
        $this->sha = $sha;

        $loose_path = $path
                      . 'objects/'
                      . substr($sha, 0, 2)
                      . '/'
                      . substr($sha, 2);

        if (!file_exists($loose_path)) {
            throw new InvalidArgumentException("Cannot open loose object file for $sha");
        }

        $raw = gzuncompress(file_get_contents($loose_path));
        $data = explode("\0", $raw, 2);

        $header = $data[0];
        $this->content = $data[1];

        list($this->type, $this->size) = explode(' ', $header);

        switch ($this->type) {
            case 'commit':
                $this->type = Raw::OBJ_COMMIT;
            break;
            case 'tree':
                $this->type = Raw::OBJ_TREE;
            break;
            case 'blob':
                $this->type = Raw::OBJ_BLOB;
            break;
            case 'tag':
                $this->type = Raw::OBJ_TAG;
            break;
            default:
                throw new UnexpectedValueException(
                     "Unexpected type '{$this->type}'"
                );
            break;
        }
    }

}
