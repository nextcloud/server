<?php
/**
 * Index - provides an 'index' object for packfile indexes
 *
 * PHP version 5.3
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT License
 * @link     http://craig0990.github.com/Granite/
 */

namespace Granite\Git\Object;
use \UnexpectedValueException as UnexpectedValueException;

/**
 * Index represents a packfile index
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT License
 * @link     http://craig0990.github.com/Granite/
 */
class Index
{
    const INDEX_MAGIC = "\377tOc";

    /**
     * The full path to the packfile index
     */
    private $path;
    /**
     * The offset at which the fanout begins, version 2+ indexes have a 2-byte header
     */
    private $offset = 8;
    /**
     * The size of the SHA-1 entries, version 1 stores 4-byte offsets alongside to
     * total 24 bytes, version 2+ stores offsets separately
     */
    private $size = 20;
    /**
     * The version of the index file format, versions 1 and 2 are in use and
     * currently supported
     */
    private $version;

    /**
     * Fetches a raw Git object and parses the result
     *
     * @param string $path     The path to the repository root
     * @param string $packname The name of the packfile index to read
     */
    public function __construct($path, $packname)
    {
        $this->path = $path
            . 'objects'
            . DIRECTORY_SEPARATOR
            . 'pack'
            . DIRECTORY_SEPARATOR
            . 'pack-' . $packname . '.idx';

        $this->version = $this->_readVersion();
        if ($this->version !== 1 && $this->version !== 2) {
            throw new UnexpectedValueException(
                "Unsupported index version (version $version)"
            );
        }

        if ($this->version == 1) {
            $this->offset = 0; // Version 1 index has no header/version
            $this->size = 24; // Offsets + SHA-1 ids are stored together
        }
    }

    /**
     * Returns the offset of the object stored in the index
     *
     * @param string $sha The SHA-1 id of the object being requested
     *
     * @return int The offset of the object in the packfile
     */
    public function find($sha)
    {
        $index = fopen($this->path, 'rb');
        $offset = false; // Offset for object in packfile not found by default

        // Read the fanout to skip to the start char in the sorted SHA-1 list
        list($start, $after) = $this->_readFanout($index, $sha);

        if ($start == $after) {
            fclose($index);
            return false; // Object is apparently located in a 0-length section
        }

        // Seek $offset + 255 4-byte fanout entries and read 256th entry
        fseek($index, $this->offset + 4 * 255);
        $totalObjects = $this->_uint32($index);

        // Look up the SHA-1 id of the object
        // TODO: Binary search
        fseek($index, $this->offset + 1024 + $this->size * $start);
        for ($i = $start; $i < $after; $i++) {
            if ($this->version == 1) {
                $offset = $this->_uint32($index);
            }

            $name = fread($index, 20);
            if ($name == pack('H40', $sha)) {
                break; // Found it
            }
        }

        if ($i == $after) {
            fclose($index);
            return false; // Scanned entire section, couldn't find it
        }

        if ($this->version == 2) {
            // Jump to the offset location and read it
            fseek($index, 1032 + 24 * $totalObjects + 4 * $i);
            $offset = $this->_uint32($index);
            if ($offset & 0x80000000) {
                // Offset is a 64-bit integer; packfile is larger than 2GB
                fclose($index);
                throw new UnexpectedValueException(
                    "Packfile larger than 2GB, currently unsupported"
                );
            }
        }

        fclose($index);
        return $offset;
    }

    /**
     * Converts a binary string into a 32-bit unsigned integer
     *
     * @param handle $file Binary string to convert
     *
     * @return int Integer value
     */
    private function _uint32($file)
    {
        $val = unpack('Nx', fread($file, 4));
        return $val['x'];
    }

    /**
     * Reads the fanout for a particular SHA-1 id
     *
     * Largely modified from Glip, with some reference to Grit - largely because I
     * can't see how to re-implement this in PHP
     *
     * @param handle $file   File handle to the index file
     * @param string $sha    The SHA-1 id to search for
     * @param int    $offset The offset at which the fanout begins
     *
     * @return array Array containing integer 'start' and
     *               'past-the-end' locations
     */
    private function _readFanout($file, $sha)
    {
        $sha = pack('H40', $sha);
        fseek($file, $this->offset);
        if ($sha{0} == "\00") {
            /**
             * First character is 0, read first fanout entry to provide
             * 'past-the-end' location (since first fanout entry provides start
             * point for '1'-prefixed SHA-1 ids)
             */
            $start = 0;
            fseek($file, $this->offset); // Jump to start of fanout, $offset bytes in
            $after = $this->_uint32($file);
        } else {
            /**
             * Take ASCII value of first character, minus one to get the fanout
             * position of the offset (minus one because the fanout does not
             * contain an entry for "\00"), multiplied by four bytes per entry
             */
            fseek($file, $this->offset + (ord($sha{0}) - 1) * 4);
            $start = $this->_uint32($file);
            $after = $this->_uint32($file);
        }

        return array($start, $after);
    }

    /**
     * Returns the version number of the index file, or 1 if there is no version
     * information
     *
     * @return int
     */
    private function _readVersion()
    {
        $file = fopen($this->path, 'rb');
        $magic = fread($file, 4);
        $version = $this->_uint32($file);

        if ($magic !== self::INDEX_MAGIC) {
            $version = 1;
        }

        fclose($file);
        return $version;
    }

}
