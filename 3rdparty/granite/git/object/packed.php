<?php
/**
 * Packed - provides a 'packed object' object
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
 * Packed represents a packed object in the Git repository
 *
 * @category Git
 * @package  Granite
 * @author   Craig Roberts <craig0990@googlemail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Expat License
 * @link     http://craig0990.github.com/Granite/
 */
class Packed extends Raw
{

    /**
     * The name of the packfile being read
     */
    private $_packfile;

    /**
     * Added to the object size to make a 'best-guess' effort at how much compressed
     * data to read - should be reimplemented, ideally with streams.
     */
    const OBJ_PADDING = 512;

    /**
     * Reads the object data from the compressed data at $offset in $packfile
     *
     * @param string $packfile The path to the packfile
     * @param int    $offset   The offset of the object data
     */
    public function __construct($packfile, $offset)
    {
        $this->_packfile = $packfile;

        list($this->type, $this->size, $this->content)
            = $this->_readPackedObject($offset);
    }

    /**
     * Reads the object data at $this->_offset
     *
     * @param int $offset Offset of the object header
     *
     * @return array Containing the type, size and object data
     */
    private function _readPackedObject($offset)
    {
        $file = fopen($this->_packfile, 'rb');
        fseek($file, $offset);
        // Read the type and uncompressed size from the object header
        list($type, $size) = $this->_readHeader($file, $offset);
        $object_offset = ftell($file);

        if ($type == self::OBJ_OFS_DELTA || $type == self::OBJ_REF_DELTA) {
            return $this->_unpackDeltified(
                $file, $offset, $object_offset, $type, $size
            );
        }

        $content = gzuncompress(fread($file, $size + self::OBJ_PADDING), $size);

        return array($type, $size, $content);
    }

    /**
     * Reads a packed object header, returning the type and the size. For more
     * detailed information, refer to the @see tag.
     *
     * From the @see tag: "Each byte is really 7 bits of data, with the first bit
     * being used to say if that hunk is the last one or not before the data starts.
     * If the first bit is a 1, you will read another byte, otherwise the data starts
     * next. The first 3 bits in the first byte specifies the type of data..."
     *
     * @param handle $file   File handle to read
     * @param int    $offset Offset of the object header
     *
     * @return array Containing the type and the size
     * @see http://book.git-scm.com/7_the_packfile.html
     */
    private function _readHeader($file, $offset)
    {
        // Read the object header byte-by-byte
        fseek($file, $offset);
        $byte = ord(fgetc($file));
        /**
         * Bit-shift right by four, then ignore the first bit with a bitwise AND
         * This gives us the object type in binary:
         *  001    commit           self::OBJ_COMMIT
         *  010    tree             self::OBJ_TREE
         *  011    blob             self::OBJ_BLOB
         *  100    tag              self::OBJ_TAG
         *  110    offset delta     self::OBJ_OFS_DELTA
         *  111    ref delta        self::OBJ_REF_DELTA
         *
         *  (000 is undefined, 101 is not currently in use)
         * See http://book.git-scm.com/7_the_packfile.html for details
         */
        $type = ($byte >> 4) & 0x07;

        // Read the last four bits of the first byte, used to find the size
        $size = $byte & 0x0F;

        /**
         * $shift initially set to four, since we use the last four bits of the first
         * byte
         *
         * $byte & 0x80 checks the initial bit is set to 1 (i.e. keep reading data)
         *
         * Finally, $shift is incremented by seven for each consecutive byte (because
         * we ignore the initial bit)
         */
        for ($shift = 4; $byte & 0x80; $shift += 7) {
            $byte = ord(fgetc($file));
            /**
             * The size is ANDed against 0x7F to strip the initial bit, then
             * bitshifted by left $shift (4 or 7, depending on whether it's the
             * initial byte) and ORed against the existing binary $size. This
             * continuously increments the $size variable.
             */
            $size |= (($byte & 0x7F) << $shift);
        }

        return array($type, $size);
    }

    /**
     * Unpacks a deltified object located at $offset in $file
     *
     * @param handle $file          File handle to read
     * @param int    $offset        Offset of the object data
     * @param int    $object_offset Offset of the object data, past the header
     * @param int    $type          The object type, either OBJ_REF_DELTA
                                    or OBJ_OFS_DELTA
     * @param int    $size          The expected size of the uncompressed data
     *
     * @return array Containing the type, size and object data
     */
    private function _unpackDeltified($file, $offset, $object_offset, $type, $size)
    {
        fseek($file, $object_offset);

        if ($type == self::OBJ_REF_DELTA) {

            $base_sha = bin2hex(fread($file, 20));

            $path = substr($this->_packfile, 0, strpos($this->_packfile, '.git')+5);
            $base = Raw::factory($path, $base_sha);
            $type = $base->type();
            $base = $base->content();

            $delta = gzuncompress(
                fread($file, $size + self::OBJ_PADDING), $size
            );

            $content = $this->_applyDelta($base, $delta);

        } elseif ($type == self::OBJ_OFS_DELTA) {

            // 20 = maximum varint size according to Glip
            $data = fread($file, $size + self::OBJ_PADDING + 20);

            list($base_offset, $length) = $this->_bigEndianNumber($data);

            $delta = gzuncompress(substr($data, $length), $size);
            unset($data);

            $base_offset = $offset - $base_offset;
            list($type, $size, $base) = $this->_readPackedObject($base_offset);

            $content = $this->_applyDelta($base, $delta);

        } else {
            throw new UnexpectedValueException(
                "Unknown type $type for deltified object"
            );
        }

        return array($type, strlen($content), $content);
    }

    /**
     * Applies the $delta byte-sequence to $base and returns the
     * resultant binary string.
     *
     * This code is modified from Grit (see below), the Ruby
     * implementation used for GitHub under an MIT license.
     *
     * @param string $base  The base string for the delta to be applied to
     * @param string $delta The delta string to apply
     *
     * @return string The patched binary string
     * @see
     * https://github.com/mojombo/grit/blob/master/lib/grit/git-ruby/internal/pack.rb
     */
    private function _applyDelta($base, $delta)
    {
        $pos = 0;
        $src_size = $this->_varint($delta, $pos);
        $dst_size = $this->_varint($delta, $pos);

        if ($src_size !== strlen($base)) {
            throw new UnexpectedValueException(
                'Expected base delta size ' . strlen($base) . ' does not match the expected '
                . "value $src_size"
            );
        }

        $dest = "";
        while ($pos < strlen($delta)) {
            $byte = ord($delta{$pos++});

      if ($byte & 0x80) {
        /* copy a part of $base */
        $offset = 0;
        if ($byte & 0x01) $offset = ord($delta{$pos++});
        if ($byte & 0x02) $offset |= ord($delta{$pos++}) <<  8;
        if ($byte & 0x04) $offset |= ord($delta{$pos++}) << 16;
        if ($byte & 0x08) $offset |= ord($delta{$pos++}) << 24;
        $length = 0;
        if ($byte & 0x10) $length = ord($delta{$pos++});
        if ($byte & 0x20) $length |= ord($delta{$pos++}) <<  8;
        if ($byte & 0x40) $length |= ord($delta{$pos++}) << 16;
        if ($length == 0) $length = 0x10000;
        $dest .= substr($base, $offset, $length);
      } else {
        /* take the next $byte bytes as they are */
        $dest .= substr($delta, $pos, $byte);
        $pos += $byte;
      }
        }

        if (strlen($dest) !== $dst_size) {
            throw new UnexpectedValueException(
                "Deltified string expected to be $dst_size bytes, but actually "
                . strlen($dest) . ' bytes'
            );
        }

        return $dest;
    }

    /**
     * Parse a Git varint (variable-length integer). Used in the `_applyDelta()`
     * method to read the delta header.
     *
     * @param string $string The string to parse
     * @param int    &$pos   The position in the string to read from
     *
     * @return int The integer value
     */
    private function _varint($string, &$pos = 0)
    {
        $varint = 0;
        $bitmask = 0x80;
        for ($i = 0; $bitmask & 0x80; $i += 7) {
            $bitmask = ord($string{$pos++});
            $varint |= (($bitmask & 0x7F) << $i);
        }
        return $varint;
    }

    /**
     * Decodes a big endian modified base 128 number (refer to @see tag); this only
     * appears to be used in one place, the offset delta in packfiles. The offset
     * is the number of bytes to seek back from the start of the delta object to find
     * the base object.
     *
     * This code has been implemented using the C code given in the @see tag below.
     *
     * @param string &$data The data to read from and decode the number
     *
     * @return Array Containing the base offset (number of bytes to seek back) and
     * the length to use when reading the delta
     * @see http://git.rsbx.net/Documents/Git_Data_Formats.txt
     */
    private function _bigEndianNumber(&$data)
    {
        $i = 0;
        $byte = ord($data{$i++});
        $number = $byte & 0x7F;
        while ($byte & 0x80) {
            $byte = ord($data{$i++});
            $number = (($number + 1) << 7) | ($byte & 0x7F);
        }

        return array($number, $i);
    }

}
