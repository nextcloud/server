<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Pure-PHP ASN.1 Parser
 *
 * PHP versions 4 and 5
 *
 * ASN.1 provides the semantics for data encoded using various schemes.  The most commonly
 * utilized scheme is DER or the "Distinguished Encoding Rules".  PEM's are base64 encoded
 * DER blobs.
 *
 * File_ASN1 decodes and encodes DER formatted messages and places them in a semantic context.
 *
 * Uses the 1988 ASN.1 syntax.
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   File
 * @package    File_ASN1
 * @author     Jim Wigginton <terrafrost@php.net>
 * @copyright  MMXII Jim Wigginton
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    $Id$
 * @link       http://phpseclib.sourceforge.net
 */

/**
 * Include Math_BigInteger
 */
if (!class_exists('Math_BigInteger')) {
    require_once('Math/BigInteger.php');
}

/**#@+
 * Tag Classes
 *
 * @access private
 * @link http://www.itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#page=12
 */
define('FILE_ASN1_CLASS_UNIVERSAL',        0);
define('FILE_ASN1_CLASS_APPLICATION',      1);
define('FILE_ASN1_CLASS_CONTEXT_SPECIFIC', 2);
define('FILE_ASN1_CLASS_PRIVATE',          3);
/**#@-*/

/**#@+
 * Tag Classes
 *
 * @access private
 * @link http://www.obj-sys.com/asn1tutorial/node124.html
 */
define('FILE_ASN1_TYPE_BOOLEAN',          1);
define('FILE_ASN1_TYPE_INTEGER',          2);
define('FILE_ASN1_TYPE_BIT_STRING',       3);
define('FILE_ASN1_TYPE_OCTET_STRING',     4);
define('FILE_ASN1_TYPE_NULL',             5);
define('FILE_ASN1_TYPE_OBJECT_IDENTIFIER',6);
//define('FILE_ASN1_TYPE_OBJECT_DESCRIPTOR',7);
//define('FILE_ASN1_TYPE_INSTANCE_OF',      8); // EXTERNAL
define('FILE_ASN1_TYPE_REAL',             9);
define('FILE_ASN1_TYPE_ENUMERATED',      10);
//define('FILE_ASN1_TYPE_EMBEDDED',        11);
define('FILE_ASN1_TYPE_UTF8_STRING',     12);
//define('FILE_ASN1_TYPE_RELATIVE_OID',    13);
define('FILE_ASN1_TYPE_SEQUENCE',        16); // SEQUENCE OF
define('FILE_ASN1_TYPE_SET',             17); // SET OF
/**#@-*/
/**#@+
 * More Tag Classes
 *
 * @access private
 * @link http://www.obj-sys.com/asn1tutorial/node10.html
 */
define('FILE_ASN1_TYPE_NUMERIC_STRING',  18);
define('FILE_ASN1_TYPE_PRINTABLE_STRING',19);
define('FILE_ASN1_TYPE_TELETEX_STRING',  20); // T61String
define('FILE_ASN1_TYPE_VIDEOTEX_STRING', 21);
define('FILE_ASN1_TYPE_IA5_STRING',      22);
define('FILE_ASN1_TYPE_UTC_TIME',        23);
define('FILE_ASN1_TYPE_GENERALIZED_TIME',24);
define('FILE_ASN1_TYPE_GRAPHIC_STRING',  25);
define('FILE_ASN1_TYPE_VISIBLE_STRING',  26); // ISO646String
define('FILE_ASN1_TYPE_GENERAL_STRING',  27);
define('FILE_ASN1_TYPE_UNIVERSAL_STRING',28);
//define('FILE_ASN1_TYPE_CHARACTER_STRING',29);
define('FILE_ASN1_TYPE_BMP_STRING',      30);
/**#@-*/

/**#@+
 * Tag Aliases
 *
 * These tags are kinda place holders for other tags.
 *
 * @access private
 */
define('FILE_ASN1_TYPE_CHOICE',          -1);
define('FILE_ASN1_TYPE_ANY',             -2);
/**#@-*/

/**
 * ASN.1 Element
 *
 * Bypass normal encoding rules in File_ASN1::encodeDER()
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @version 0.3.0
 * @access  public
 * @package File_ASN1
 */
class File_ASN1_Element {
    /**
     * Raw element value
     *
     * @var String
     * @access private
     */
    var $element;

    /**
     * Constructor
     *
     * @param String $encoded
     * @return File_ASN1_Element
     * @access public
     */
    function File_ASN1_Element($encoded)
    {
        $this->element = $encoded;
    }
}

/**
 * Pure-PHP ASN.1 Parser
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @version 0.3.0
 * @access  public
 * @package File_ASN1
 */
class File_ASN1 {
    /**
     * ASN.1 object identifier
     *
     * @var Array
     * @access private
     * @link http://en.wikipedia.org/wiki/Object_identifier
     */
    var $oids = array();

    /**
     * Default date format
     *
     * @var String
     * @access private
     * @link http://php.net/class.datetime
     */
    var $format = 'D, d M y H:i:s O';

    /**
     * Default date format
     *
     * @var Array
     * @access private
     * @see File_ASN1::setTimeFormat()
     * @see File_ASN1::asn1map()
     * @link http://php.net/class.datetime
     */
    var $encoded;

    /**
     * Filters
     *
     * If the mapping type is FILE_ASN1_TYPE_ANY what do we actually encode it as?
     *
     * @var Array
     * @access private
     * @see File_ASN1::_encode_der()
     */
    var $filters;

    /**
     * Type mapping table for the ANY type.
     *
     * Structured or unknown types are mapped to a FILE_ASN1_Element.
     * Unambiguous types get the direct mapping (int/real/bool).
     * Others are mapped as a choice, with an extra indexing level.
     *
     * @var Array
     * @access public
     */
    var $ANYmap = array(
        FILE_ASN1_TYPE_BOOLEAN              => true,
        FILE_ASN1_TYPE_INTEGER              => true,
        FILE_ASN1_TYPE_BIT_STRING           => 'bitString',
        FILE_ASN1_TYPE_OCTET_STRING         => 'octetString',
        FILE_ASN1_TYPE_NULL                 => 'null',
        FILE_ASN1_TYPE_OBJECT_IDENTIFIER    => 'objectIdentifier',
        FILE_ASN1_TYPE_REAL                 => true,
        FILE_ASN1_TYPE_ENUMERATED           => 'enumerated',
        FILE_ASN1_TYPE_UTF8_STRING          => 'utf8String',
        FILE_ASN1_TYPE_NUMERIC_STRING       => 'numericString',
        FILE_ASN1_TYPE_PRINTABLE_STRING     => 'printableString',
        FILE_ASN1_TYPE_TELETEX_STRING       => 'teletexString',
        FILE_ASN1_TYPE_VIDEOTEX_STRING      => 'videotexString',
        FILE_ASN1_TYPE_IA5_STRING           => 'ia5String',
        FILE_ASN1_TYPE_UTC_TIME             => 'utcTime',
        FILE_ASN1_TYPE_GENERALIZED_TIME     => 'generalTime',
        FILE_ASN1_TYPE_GRAPHIC_STRING       => 'graphicString',
        FILE_ASN1_TYPE_VISIBLE_STRING       => 'visibleString',
        FILE_ASN1_TYPE_GENERAL_STRING       => 'generalString',
        FILE_ASN1_TYPE_UNIVERSAL_STRING     => 'universalString',
        //FILE_ASN1_TYPE_CHARACTER_STRING     => 'characterString',
        FILE_ASN1_TYPE_BMP_STRING           => 'bmpString'
    );

    /**
     * String type to character size mapping table.
     *
     * Non-convertable types are absent from this table.
     * size == 0 indicates variable length encoding.
     *
     * @var Array
     * @access public
     */
    var $stringTypeSize = array(
        FILE_ASN1_TYPE_UTF8_STRING      => 0,
        FILE_ASN1_TYPE_BMP_STRING       => 2,
        FILE_ASN1_TYPE_UNIVERSAL_STRING => 4,
        FILE_ASN1_TYPE_PRINTABLE_STRING => 1,
        FILE_ASN1_TYPE_TELETEX_STRING   => 1,
        FILE_ASN1_TYPE_IA5_STRING       => 1,
        FILE_ASN1_TYPE_VISIBLE_STRING   => 1,
    );

    /**
     * Parse BER-encoding
     *
     * Serves a similar purpose to openssl's asn1parse
     *
     * @param String $encoded
     * @return Array
     * @access public
     */
    function decodeBER($encoded)
    {
        if (is_object($encoded) && strtolower(get_class($encoded)) == 'file_asn1_element') {
            $encoded = $encoded->element;
        }

        $this->encoded = $encoded;
        return $this->_decode_ber($encoded);
    }

    /**
     * Parse BER-encoding (Helper function)
     *
     * Sometimes we want to get the BER encoding of a particular tag.  $start lets us do that without having to reencode.
     * $encoded is passed by reference for the recursive calls done for FILE_ASN1_TYPE_BIT_STRING and
     * FILE_ASN1_TYPE_OCTET_STRING. In those cases, the indefinite length is used.
     *
     * @param String $encoded
     * @param Integer $start
     * @return Array
     * @access private
     */
    function _decode_ber(&$encoded, $start = 0)
    {
        $decoded = array();

        while ( strlen($encoded) ) {
            $current = array('start' => $start);

            $type = ord($this->_string_shift($encoded));
            $start++;

            $constructed = ($type >> 5) & 1;

            $tag = $type & 0x1F;
            if ($tag == 0x1F) {
                $tag = 0;
                // process septets (since the eighth bit is ignored, it's not an octet)
                do {
                    $loop = ord($encoded[0]) >> 7;
                    $tag <<= 7;
                    $tag |= ord($this->_string_shift($encoded)) & 0x7F;
                    $start++;
                } while ( $loop );
            }

            // Length, as discussed in § 8.1.3 of X.690-0207.pdf#page=13
            $length = ord($this->_string_shift($encoded));
            $start++;
            if ( $length == 0x80 ) { // indefinite length
                // "[A sender shall] use the indefinite form (see 8.1.3.6) if the encoding is constructed and is not all 
                //  immediately available." -- § 8.1.3.2.c
                //if ( !$constructed ) {
                //    return false;
                //}
                $length = strlen($encoded);
            } elseif ( $length & 0x80 ) { // definite length, long form
                // technically, the long form of the length can be represented by up to 126 octets (bytes), but we'll only
                // support it up to four.
                $length&= 0x7F;
                $temp = $this->_string_shift($encoded, $length);
                $start+= $length;
                extract(unpack('Nlength', substr(str_pad($temp, 4, chr(0), STR_PAD_LEFT), -4)));
            }

            // End-of-content, see §§ 8.1.1.3, 8.1.3.2, 8.1.3.6, 8.1.5, and (for an example) 8.6.4.2
            if (!$type && !$length) {
                return $decoded;
            }
            $content = $this->_string_shift($encoded, $length);

            /* Class is UNIVERSAL, APPLICATION, PRIVATE, or CONTEXT-SPECIFIC. The UNIVERSAL class is restricted to the ASN.1
               built-in types. It defines an application-independent data type that must be distinguishable from all other
               data types. The other three classes are user defined. The APPLICATION class distinguishes data types that
               have a wide, scattered use within a particular presentation context. PRIVATE distinguishes data types within
               a particular organization or country. CONTEXT-SPECIFIC distinguishes members of a sequence or set, the
               alternatives of a CHOICE, or universally tagged set members. Only the class number appears in braces for this
               data type; the term CONTEXT-SPECIFIC does not appear.

                 -- http://www.obj-sys.com/asn1tutorial/node12.html */
            $class = ($type >> 6) & 3;
            switch ($class) {
                case FILE_ASN1_CLASS_APPLICATION:
                case FILE_ASN1_CLASS_PRIVATE:
                case FILE_ASN1_CLASS_CONTEXT_SPECIFIC:
                    $decoded[] = array(
                        'type'     => $class,
                        'constant' => $tag,
                        'content'  => $constructed ? $this->_decode_ber($content, $start) : $content,
                        'length'   => $length + $start - $current['start']
                    ) + $current;
                    continue 2;
            }

            $current+= array('type' => $tag);

            // decode UNIVERSAL tags
            switch ($tag) {
                case FILE_ASN1_TYPE_BOOLEAN:
                    // "The contents octets shall consist of a single octet." -- § 8.2.1
                    //if (strlen($content) != 1) {
                    //    return false;
                    //}
                    $current['content'] = (bool) ord($content[0]);
                    break;
                case FILE_ASN1_TYPE_INTEGER:
                case FILE_ASN1_TYPE_ENUMERATED:
                    $current['content'] = new Math_BigInteger($content, -256);
                    break;
                case FILE_ASN1_TYPE_REAL: // not currently supported
                    return false;
                case FILE_ASN1_TYPE_BIT_STRING:
                    // The initial octet shall encode, as an unsigned binary integer with bit 1 as the least significant bit,
                    // the number of unused bits in the final subsequent octet. The number shall be in the range zero to
                    // seven.
                    if (!$constructed) {
                        $current['content'] = $content;
                    } else {
                        $temp = $this->_decode_ber($content, $start);
                        $length-= strlen($content);
                        $last = count($temp) - 1;
                        for ($i = 0; $i < $last; $i++) {
                            // all subtags should be bit strings
                            //if ($temp[$i]['type'] != FILE_ASN1_TYPE_BIT_STRING) {
                            //    return false;
                            //}
                            $current['content'].= substr($temp[$i]['content'], 1);
                        }
                        // all subtags should be bit strings
                        //if ($temp[$last]['type'] != FILE_ASN1_TYPE_BIT_STRING) {
                        //    return false;
                        //}
                        $current['content'] = $temp[$last]['content'][0] . $current['content'] . substr($temp[$i]['content'], 1);
                    }
                    break;
                case FILE_ASN1_TYPE_OCTET_STRING:
                    if (!$constructed) {
                        $current['content'] = $content;
                    } else {
                        $temp = $this->_decode_ber($content, $start);
                        $length-= strlen($content);
                        for ($i = 0, $size = count($temp); $i < $size; $i++) {
                            // all subtags should be octet strings
                            //if ($temp[$i]['type'] != FILE_ASN1_TYPE_OCTET_STRING) {
                            //    return false;
                            //}
                            $current['content'].= $temp[$i]['content'];
                        }
                        // $length = 
                    }
                    break;
                case FILE_ASN1_TYPE_NULL:
                    // "The contents octets shall not contain any octets." -- § 8.8.2
                    //if (strlen($content)) {
                    //    return false;
                    //}
                    break;
                case FILE_ASN1_TYPE_SEQUENCE:
                case FILE_ASN1_TYPE_SET:
                    $current['content'] = $this->_decode_ber($content, $start);
                    break;
                case FILE_ASN1_TYPE_OBJECT_IDENTIFIER:
                    $temp = ord($this->_string_shift($content));
                    $current['content'] = sprintf('%d.%d', floor($temp / 40), $temp % 40);
                    $valuen = 0;
                    // process septets
                    while (strlen($content)) {
                        $temp = ord($this->_string_shift($content));
                        $valuen <<= 7;
                        $valuen |= $temp & 0x7F;
                        if (~$temp & 0x80) {
                            $current['content'].= ".$valuen";
                            $valuen = 0;
                        }
                    }
                    // the eighth bit of the last byte should not be 1
                    //if ($temp >> 7) {
                    //    return false;
                    //}
                    break;
                /* Each character string type shall be encoded as if it had been declared:
                   [UNIVERSAL x] IMPLICIT OCTET STRING

                     -- X.690-0207.pdf#page=23 (§ 8.21.3)

                   Per that, we're not going to do any validation.  If there are any illegal characters in the string, 
                   we don't really care */
                case FILE_ASN1_TYPE_NUMERIC_STRING:
                    // 0,1,2,3,4,5,6,7,8,9, and space
                case FILE_ASN1_TYPE_PRINTABLE_STRING:
                    // Upper and lower case letters, digits, space, apostrophe, left/right parenthesis, plus sign, comma,
                    // hyphen, full stop, solidus, colon, equal sign, question mark
                case FILE_ASN1_TYPE_TELETEX_STRING:
                    // The Teletex character set in CCITT's T61, space, and delete
                    // see http://en.wikipedia.org/wiki/Teletex#Character_sets
                case FILE_ASN1_TYPE_VIDEOTEX_STRING:
                    // The Videotex character set in CCITT's T.100 and T.101, space, and delete
                case FILE_ASN1_TYPE_VISIBLE_STRING:
                    // Printing character sets of international ASCII, and space
                case FILE_ASN1_TYPE_IA5_STRING:
                    // International Alphabet 5 (International ASCII)
                case FILE_ASN1_TYPE_GRAPHIC_STRING:
                    // All registered G sets, and space
                case FILE_ASN1_TYPE_GENERAL_STRING:
                    // All registered C and G sets, space and delete
                case FILE_ASN1_TYPE_UTF8_STRING:
                    // ????
                case FILE_ASN1_TYPE_BMP_STRING:
                    $current['content'] = $content;
                    break;
                case FILE_ASN1_TYPE_UTC_TIME:
                case FILE_ASN1_TYPE_GENERALIZED_TIME:
                    $current['content'] = $this->_decodeTime($content, $tag);
                default:

            }

            $start+= $length;
            $decoded[] = $current + array('length' => $start - $current['start']);
        }

        return $decoded;
    }

    /**
     * ASN.1 Decode
     *
     * Provides an ASN.1 semantic mapping ($mapping) from a parsed BER-encoding to a human readable format.
     *
     * @param Array $decoded
     * @param Array $mapping
     * @return Array
     * @access public
     */
    function asn1map($decoded, $mapping)
    {
        if (isset($mapping['explicit'])) {
            $decoded = $decoded['content'][0];
        }

        switch (true) {
            case $mapping['type'] == FILE_ASN1_TYPE_ANY:
                $intype = $decoded['type'];
                if (isset($decoded['constant']) || !isset($this->ANYmap[$intype]) || ($this->encoded[$decoded['start']] & 0x20)) {
                    return new File_ASN1_Element(substr($this->encoded, $decoded['start'], $decoded['length']));
                }
                $inmap = $this->ANYmap[$intype];
                if (is_string($inmap)) {
                    return array($inmap => $this->asn1map($decoded, array('type' => $intype) + $mapping));
                }
                break;
            case $mapping['type'] == FILE_ASN1_TYPE_CHOICE:
                foreach ($mapping['children'] as $key => $option) {
                    switch (true) {
                        case isset($option['constant']) && $option['constant'] == $decoded['constant']:
                        case !isset($option['constant']) && $option['type'] == $decoded['type']:
                            $value = $this->asn1map($decoded, $option);
                    }
                    if (isset($value)) {
                        return array($key => $value);
                    }
                }
                return NULL;
            case isset($mapping['implicit']):
            case isset($mapping['explicit']):
            case $decoded['type'] == $mapping['type']:
                break;
            default:
                return NULL;
        }

        if (isset($mapping['implicit'])) {
            $decoded['type'] = $mapping['type'];
        }

        switch ($decoded['type']) {
            case FILE_ASN1_TYPE_SEQUENCE:
                $map = array();

                if (empty($decoded['content'])) {
                    return $map;
                }

                // ignore the min and max
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $child = $mapping['children'];
                    foreach ($decoded['content'] as $content) {
                        $map[] = $this->asn1map($content, $child);
                    }
                    return $map;
                }

                $temp = $decoded['content'][$i = 0];
                foreach ($mapping['children'] as $key => $child) {
                    if (!isset($child['optional']) && $child['type'] == FILE_ASN1_TYPE_CHOICE) {
                        $map[$key] = $this->asn1map($temp, $child);
                        $i++;
                        if (count($decoded['content']) == $i) {
                            break;
                        }
                        $temp = $decoded['content'][$i];
                        continue;
                    }

                    $childClass = $tempClass = FILE_ASN1_CLASS_UNIVERSAL;
                    $constant = NULL;
                    if (isset($temp['constant'])) {
                        $tempClass = isset($temp['class']) ? $temp['class'] : FILE_ASN1_CLASS_CONTEXT_SPECIFIC;
                    }
                    if (isset($child['class'])) {
                        $childClass = $child['class'];
                        $constant = $child['cast'];
                    }
                    elseif (isset($child['constant'])) {
                        $childClass = FILE_ASN1_CLASS_CONTEXT_SPECIFIC;
                        $constant = $child['constant'];
                    }

                    if (isset($child['optional'])) {
                        if (isset($constant) && isset($temp['constant'])) {
                            if (($constant == $temp['constant']) && ($childClass == $tempClass)) {
                                $map[$key] = $this->asn1map($temp, $child);
                                $i++;
                                if (count($decoded['content']) == $i) {
                                    break;
                                }
                                $temp = $decoded['content'][$i];
                            }
                        } elseif (!isset($child['constant'])) {
                            // we could do this, as well:
                            // $buffer = $this->asn1map($temp, $child); if (isset($buffer)) { $map[$key] = $buffer; }
                            if ($child['type'] == $temp['type'] || $child['type'] == FILE_ASN1_TYPE_ANY) {
                                $map[$key] = $this->asn1map($temp, $child);
                                $i++;
                                if (count($decoded['content']) == $i) {
                                    break;
                                }
                                $temp = $decoded['content'][$i];
                            } elseif ($child['type'] == FILE_ASN1_TYPE_CHOICE) {
                                $candidate = $this->asn1map($temp, $child);
                                if (!empty($candidate)) {
                                    $map[$key] = $candidate;
                                    $i++;
                                    if (count($decoded['content']) == $i) {
                                        break;
                                    }
                                    $temp = $decoded['content'][$i];
                                }
                            }
                        }

                        if (!isset($map[$key]) && isset($child['default'])) {
                            $map[$key] = $child['default'];
                        }
                    } else {
                        $map[$key] = $this->asn1map($temp, $child);
                        $i++;
                        if (count($decoded['content']) == $i) {
                            break;
                        }
                        $temp = $decoded['content'][$i];
                    }
                }

                return $map;
            // the main diff between sets and sequences is the encapsulation of the foreach in another for loop
            case FILE_ASN1_TYPE_SET:
                $map = array();

                // ignore the min and max
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $child = $mapping['children'];
                    foreach ($decoded['content'] as $content) {
                        $map[] = $this->asn1map($content, $child);
                    }

                    return $map;
                }

                for ($i = 0; $i < count($decoded['content']); $i++) {
                    foreach ($mapping['children'] as $key => $child) {
                        $temp = $decoded['content'][$i];

                        if (!isset($child['optional']) && $child['type'] == FILE_ASN1_TYPE_CHOICE) {
                            $map[$key] = $this->asn1map($temp, $child);
                            continue;
                        }

                        $childClass = $tempClass = FILE_ASN1_CLASS_UNIVERSAL;
                        $constant = NULL;
                        if (isset($temp['constant'])) {
                            $tempClass = isset($temp['class']) ? $temp['class'] : FILE_ASN1_CLASS_CONTEXT_SPECIFIC;
                        }
                        if (isset($child['class'])) {
                            $childClass = $child['class'];
                            $constant = $child['cast'];
                        }
                        elseif (isset($child['constant'])) {
                            $childClass = FILE_ASN1_CLASS_CONTEXT_SPECIFIC;
                            $constant = $child['constant'];
                        }

                        if (isset($constant) && isset($temp['constant'])) {
                            if (($constant == $temp['constant']) && ($childClass == $tempClass)) {
                                $map[$key] = $this->asn1map($temp['content'], $child);
                            }
                        } elseif (!isset($child['constant'])) {
                            // we could do this, as well:
                            // $buffer = $this->asn1map($temp['content'], $child); if (isset($buffer)) { $map[$key] = $buffer; }
                            if ($child['type'] == $temp['type']) {
                                $map[$key] = $this->asn1map($temp, $child);
                            }
                        }
                    }
                }

                foreach ($mapping['children'] as $key => $child) {
                    if (!isset($map[$key]) && isset($child['default'])) {
                        $map[$key] = $child['default'];
                    }
                }
                return $map;
            case FILE_ASN1_TYPE_OBJECT_IDENTIFIER:
                return isset($this->oids[$decoded['content']]) ? $this->oids[$decoded['content']] : $decoded['content'];
            case FILE_ASN1_TYPE_UTC_TIME:
            case FILE_ASN1_TYPE_GENERALIZED_TIME:
                if (isset($mapping['implicit'])) {
                    $decoded['content'] = $this->_decodeTime($decoded['content'], $decoded['type']);
                }
                return @date($this->format, $decoded['content']);
            case FILE_ASN1_TYPE_BIT_STRING:
                if (isset($mapping['mapping'])) {
                    $offset = ord($decoded['content'][0]);
                    $size = (strlen($decoded['content']) - 1) * 8 - $offset;
                    /*
                       From X.680-0207.pdf#page=46 (21.7):

                       "When a "NamedBitList" is used in defining a bitstring type ASN.1 encoding rules are free to add (or remove)
                        arbitrarily any trailing 0 bits to (or from) values that are being encoded or decoded. Application designers should
                        therefore ensure that different semantics are not associated with such values which differ only in the number of trailing
                        0 bits."
                    */
                    $bits = count($mapping['mapping']) == $size ? array() : array_fill(0, count($mapping['mapping']) - $size, false);
                    for ($i = strlen($decoded['content']) - 1; $i > 0; $i--) {
                        $current = ord($decoded['content'][$i]);
                        for ($j = $offset; $j < 8; $j++) {
                            $bits[] = (bool) ($current & (1 << $j));
                        }
                        $offset = 0;
                    }
                    $values = array();
                    $map = array_reverse($mapping['mapping']);
                    foreach ($map as $i => $value) {
                        if ($bits[$i]) {
                            $values[] = $value;
                        }
                    }
                    return $values;
                }
            case FILE_ASN1_TYPE_OCTET_STRING:
                return base64_encode($decoded['content']);
            case FILE_ASN1_TYPE_NULL:
                return '';
            case FILE_ASN1_TYPE_BOOLEAN:
                return $decoded['content'];
            case FILE_ASN1_TYPE_NUMERIC_STRING:
            case FILE_ASN1_TYPE_PRINTABLE_STRING:
            case FILE_ASN1_TYPE_TELETEX_STRING:
            case FILE_ASN1_TYPE_VIDEOTEX_STRING:
            case FILE_ASN1_TYPE_IA5_STRING:
            case FILE_ASN1_TYPE_GRAPHIC_STRING:
            case FILE_ASN1_TYPE_VISIBLE_STRING:
            case FILE_ASN1_TYPE_GENERAL_STRING:
            case FILE_ASN1_TYPE_UNIVERSAL_STRING:
            case FILE_ASN1_TYPE_UTF8_STRING:
            case FILE_ASN1_TYPE_BMP_STRING:
                return $decoded['content'];
            case FILE_ASN1_TYPE_INTEGER:
            case FILE_ASN1_TYPE_ENUMERATED:
                $temp = $decoded['content'];
                if (isset($mapping['implicit'])) {
                    $temp = new Math_BigInteger($decoded['content'], -256);
                }
                if (isset($mapping['mapping'])) {
                    $temp = (int) $temp->toString();
                    return isset($mapping['mapping'][$temp]) ?
                        $mapping['mapping'][$temp] :
                        false;
                }
                return $temp;
        }
    }

    /**
     * ASN.1 Encode
     *
     * DER-encodes an ASN.1 semantic mapping ($mapping).  Some libraries would probably call this function
     * an ASN.1 compiler.
     *
     * @param String $source
     * @param String $mapping
     * @param Integer $idx
     * @return String
     * @access public
     */
    function encodeDER($source, $mapping)
    {
        $this->location = array();
        return $this->_encode_der($source, $mapping);
    }

    /**
     * ASN.1 Encode (Helper function)
     *
     * @param String $source
     * @param String $mapping
     * @param Integer $idx
     * @return String
     * @access private
     */
    function _encode_der($source, $mapping, $idx = NULL)
    {
        if (is_object($source) && strtolower(get_class($source)) == 'file_asn1_element') {
            return $source->element;
        }

        // do not encode (implicitly optional) fields with value set to default
        if (isset($mapping['default']) && $source === $mapping['default']) {
            return '';
        }

        if (isset($idx)) {
            $this->location[] = $idx;
        }

        $tag = $mapping['type'];

        switch ($tag) {
            case FILE_ASN1_TYPE_SET:    // Children order is not important, thus process in sequence.
            case FILE_ASN1_TYPE_SEQUENCE:
                $tag|= 0x20; // set the constructed bit
                $value = '';

                // ignore the min and max
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $child = $mapping['children'];

                    foreach ($source as $content) {
                        $temp = $this->_encode_der($content, $child);
                        if ($temp === false) {
                            return false;
                        }
                        $value.= $temp;
                    }
                    break;
                }

                foreach ($mapping['children'] as $key => $child) {
                    if (!isset($source[$key])) {
                        if (!isset($child['optional'])) {
                            return false;
                        }
                        continue;
                    }

                    $temp = $this->_encode_der($source[$key], $child, $key);
                    if ($temp === false) {
                        return false;
                    }

                    // An empty child encoding means it has been optimized out.
                    // Else we should have at least one tag byte.
                    if ($temp === '') {
                        continue;
                    }

                    // if isset($child['constant']) is true then isset($child['optional']) should be true as well
                    if (isset($child['constant'])) {
                        /*
                           From X.680-0207.pdf#page=58 (30.6):

                           "The tagging construction specifies explicit tagging if any of the following holds:
                            ...
                            c) the "Tag Type" alternative is used and the value of "TagDefault" for the module is IMPLICIT TAGS or
                            AUTOMATIC TAGS, but the type defined by "Type" is an untagged choice type, an untagged open type, or
                            an untagged "DummyReference" (see ITU-T Rec. X.683 | ISO/IEC 8824-4, 8.3)."
                         */
                        if (isset($child['explicit']) || $child['type'] == FILE_ASN1_TYPE_CHOICE) {
                            $subtag = chr((FILE_ASN1_CLASS_CONTEXT_SPECIFIC << 6) | 0x20 | $child['constant']);
                            $temp = $subtag . $this->_encodeLength(strlen($temp)) . $temp;
                        } else {
                            $subtag = chr((FILE_ASN1_CLASS_CONTEXT_SPECIFIC << 6) | (ord($temp[0]) & 0x20) | $child['constant']);
                            $temp = $subtag . substr($temp, 1);
                        }
                    }
                    $value.= $temp;
                }
                break;
            case FILE_ASN1_TYPE_CHOICE:
                $temp = false;

                foreach ($mapping['children'] as $key => $child) {
                    if (!isset($source[$key])) {
                        continue;
                    }

                    $temp = $this->_encode_der($source[$key], $child, $key);
                    if ($temp === false) {
                        return false;
                    }

                    // An empty child encoding means it has been optimized out.
                    // Else we should have at least one tag byte.
                    if ($temp === '') {
                        continue;
                    }

                    $tag = ord($temp[0]);

                    // if isset($child['constant']) is true then isset($child['optional']) should be true as well
                    if (isset($child['constant'])) {
                        if (isset($child['explicit']) || $child['type'] == FILE_ASN1_TYPE_CHOICE) {
                            $subtag = chr((FILE_ASN1_CLASS_CONTEXT_SPECIFIC << 6) | 0x20 | $child['constant']);
                            $temp = $subtag . $this->_encodeLength(strlen($temp)) . $temp;
                        } else {
                            $subtag = chr((FILE_ASN1_CLASS_CONTEXT_SPECIFIC << 6) | (ord($temp[0]) & 0x20) | $child['constant']);
                            $temp = $subtag . substr($temp, 1);
                        }
                    }
                }

                if (isset($idx)) {
                    array_pop($this->location);
                }

                if ($temp && isset($mapping['cast'])) {
                    $temp[0] = chr(($mapping['class'] << 6) | ($tag & 0x20) | $mapping['cast']);
                }

                return $temp;
            case FILE_ASN1_TYPE_INTEGER:
            case FILE_ASN1_TYPE_ENUMERATED:
                if (!isset($mapping['mapping'])) {
                    $value = $source->toBytes(true);
                } else {
                    $value = array_search($source, $mapping['mapping']);
                    if ($value === false) {
                        return false;
                    }
                    $value = new Math_BigInteger($value);
                    $value = $value->toBytes(true);
                }
                break;
            case FILE_ASN1_TYPE_UTC_TIME:
            case FILE_ASN1_TYPE_GENERALIZED_TIME:
                $format = $mapping['type'] == FILE_ASN1_TYPE_UTC_TIME ? 'y' : 'Y';
                $format.= 'mdHis';
                $value = @gmdate($format, strtotime($source)) . 'Z';
                break;
            case FILE_ASN1_TYPE_BIT_STRING:
                if (isset($mapping['mapping'])) {
                    $bits = array_fill(0, count($mapping['mapping']), 0);
                    $size = 0;
                    for ($i = 0; $i < count($mapping['mapping']); $i++) {
                        if (in_array($mapping['mapping'][$i], $source)) {
                            $bits[$i] = 1;
                            $size = $i;
                        }
                    }

                    $offset = 8 - (($size + 1) & 7);
                    $offset = $offset !== 8 ? $offset : 0;

                    $value = chr($offset);

                    for ($i = $size + 1; $i < count($mapping['mapping']); $i++) {
                        unset($bits[$i]);
                    }

                    $bits = implode('', array_pad($bits, $size + $offset + 1, 0));
                    $bytes = explode(' ', rtrim(chunk_split($bits, 8, ' ')));
                    foreach ($bytes as $byte) {
                        $value.= chr(bindec($byte));
                    }

                    break;
                }
            case FILE_ASN1_TYPE_OCTET_STRING:
                /* The initial octet shall encode, as an unsigned binary integer with bit 1 as the least significant bit,
                   the number of unused bits in the final subsequent octet. The number shall be in the range zero to seven.

                   -- http://www.itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#page=16 */
                $value = base64_decode($source);
                break;
            case FILE_ASN1_TYPE_OBJECT_IDENTIFIER:
                $oid = preg_match('#(?:\d+\.)+#', $source) ? $source : array_search($source, $this->oids);
                if ($oid === false) {
                    user_error('Invalid OID', E_USER_NOTICE);
                    return false;
                }
                $value = '';
                $parts = explode('.', $oid);
                $value = chr(40 * $parts[0] + $parts[1]);
                for ($i = 2; $i < count($parts); $i++) {
                    $temp = '';
                    if (!$parts[$i]) {
                        $temp = "\0";
                    } else {
                        while ($parts[$i]) {
                            $temp = chr(0x80 | ($parts[$i] & 0x7F)) . $temp;
                            $parts[$i] >>= 7;
                        }
                        $temp[strlen($temp) - 1] = $temp[strlen($temp) - 1] & chr(0x7F);
                    }
                    $value.= $temp;
                }
                break;
            case FILE_ASN1_TYPE_ANY:
                $loc = $this->location;
                if (isset($idx)) {
                    array_pop($this->location);
                }

                switch (true) {
                    case !isset($source):
                        return $this->_encode_der(NULL, array('type' => FILE_ASN1_TYPE_NULL) + $mapping);
                    case is_int($source):
                    case is_object($source) && strtolower(get_class($source)) == 'math_biginteger':
                        return $this->_encode_der($source, array('type' => FILE_ASN1_TYPE_INTEGER) + $mapping);
                    case is_float($source):
                        return $this->_encode_der($source, array('type' => FILE_ASN1_TYPE_REAL) + $mapping);
                    case is_bool($source):
                        return $this->_encode_der($source, array('type' => FILE_ASN1_TYPE_BOOLEAN) + $mapping);
                    case is_array($source) && count($source) == 1:
                        $typename = implode('', array_keys($source));
                        $outtype = array_search($typename, $this->ANYmap, true);
                        if ($outtype !== false) {
                            return $this->_encode_der($source[$typename], array('type' => $outtype) + $mapping);
                        }
                    }

                $filters = $this->filters;
                foreach ($loc as $part) {
                    if (!isset($filters[$part])) {
                        $filters = false;
                        break;
                    }
                    $filters = $filters[$part];
                }
                if ($filters === false) {
                    user_error('No filters defined for ' . implode('/', $loc), E_USER_NOTICE);
                    return false;
                }
                return $this->_encode_der($source, $filters + $mapping);
            case FILE_ASN1_TYPE_NULL:
                $value = '';
                break;
            case FILE_ASN1_TYPE_NUMERIC_STRING:
            case FILE_ASN1_TYPE_TELETEX_STRING:
            case FILE_ASN1_TYPE_PRINTABLE_STRING:
            case FILE_ASN1_TYPE_UNIVERSAL_STRING:
            case FILE_ASN1_TYPE_UTF8_STRING:
            case FILE_ASN1_TYPE_BMP_STRING:
            case FILE_ASN1_TYPE_IA5_STRING:
            case FILE_ASN1_TYPE_VISIBLE_STRING:
            case FILE_ASN1_TYPE_VIDEOTEX_STRING:
            case FILE_ASN1_TYPE_GRAPHIC_STRING:
            case FILE_ASN1_TYPE_GENERAL_STRING:
                $value = $source;
                break;
            case FILE_ASN1_TYPE_BOOLEAN:
                $value = $source ? "\xFF" : "\x00";
                break;
            default:
                user_error('Mapping provides no type definition for ' . implode('/', $this->location), E_USER_NOTICE);
                return false;
        }

        if (isset($idx)) {
            array_pop($this->location);
        }

        if (isset($mapping['cast'])) {
            $tag = ($mapping['class'] << 6) | ($tag & 0x20) | $mapping['cast'];
        }

        return chr($tag) . $this->_encodeLength(strlen($value)) . $value;
    }

    /**
     * DER-encode the length
     *
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.  See
     * {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 § 8.1.3} for more information.
     *
     * @access private
     * @param Integer $length
     * @return String
     */
    function _encodeLength($length)
    {
        if ($length <= 0x7F) {
            return chr($length);
        }

        $temp = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }

    /**
     * BER-decode the time
     *
     * Called by _decode_ber() and in the case of implicit tags asn1map().
     *
     * @access private
     * @param String $content
     * @param Integer $tag
     * @return String
     */
    function _decodeTime($content, $tag)
    {
        /* UTCTime:
           http://tools.ietf.org/html/rfc5280#section-4.1.2.5.1
           http://www.obj-sys.com/asn1tutorial/node15.html

           GeneralizedTime:
           http://tools.ietf.org/html/rfc5280#section-4.1.2.5.2
           http://www.obj-sys.com/asn1tutorial/node14.html */

        $pattern = $tag == FILE_ASN1_TYPE_UTC_TIME ?
            '#(..)(..)(..)(..)(..)(..)(.*)#' :
            '#(....)(..)(..)(..)(..)(..).*([Z+-].*)$#';

        preg_match($pattern, $content, $matches);

        list(, $year, $month, $day, $hour, $minute, $second, $timezone) = $matches;

        if ($tag == FILE_ASN1_TYPE_UTC_TIME) {
            $year = $year >= 50 ? "19$year" : "20$year";
        }

        if ($timezone == 'Z') {
            $mktime = 'gmmktime';
            $timezone = 0;
        } elseif (preg_match('#([+-])(\d\d)(\d\d)#', $timezone, $matches)) {
            $mktime = 'gmmktime';
            $timezone = 60 * $matches[3] + 3600 * $matches[2];
            if ($matches[1] == '-') {
                $timezone = -$timezone;
            }
        } else {
            $mktime = 'mktime';
            $timezone = 0;
        }

        return @$mktime($hour, $minute, $second, $month, $day, $year) + $timezone;
    }

    /**
     * Set the time format
     *
     * Sets the time / date format for asn1map().
     *
     * @access public
     * @param String $format
     */
    function setTimeFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Load OIDs
     *
     * Load the relevant OIDs for a particular ASN.1 semantic mapping.
     *
     * @access public
     * @param Array $oids
     */
    function loadOIDs($oids)
    {
        $this->oids = $oids;
    }

    /**
     * Load filters
     *
     * See File_X509, etc, for an example.
     *
     * @access public
     * @param Array $filters
     */
    function loadFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * String Shift
     *
     * Inspired by array_shift
     *
     * @param String $string
     * @param optional Integer $index
     * @return String
     * @access private
     */
    function _string_shift(&$string, $index = 1)
    {
        $substr = substr($string, 0, $index);
        $string = substr($string, $index);
        return $substr;
    }

    /**
     * String type conversion
     *
     * This is a lazy conversion, dealing only with character size.
     * No real conversion table is used.
     *
     * @param String $in
     * @param optional Integer $from
     * @param optional Integer $to
     * @return String
     * @access public
     */
    function convert($in, $from = FILE_ASN1_TYPE_UTF8_STRING, $to = FILE_ASN1_TYPE_UTF8_STRING)
    {
        if (!isset($this->stringTypeSize[$from]) || !isset($this->stringTypeSize[$to])) {
            return false;
        }
        $insize = $this->stringTypeSize[$from];
        $outsize = $this->stringTypeSize[$to];
        $inlength = strlen($in);
        $out = '';

        for ($i = 0; $i < $inlength;) {
            if ($inlength - $i < $insize) {
                return false;
            }

            // Get an input character as a 32-bit value.
            $c = ord($in[$i++]);
            switch (true) {
                case $insize == 4:
                    $c = ($c << 8) | ord($in[$i++]);
                    $c = ($c << 8) | ord($in[$i++]);
                case $insize == 2:
                    $c = ($c << 8) | ord($in[$i++]);
                case $insize == 1:
                    break;
                case ($c & 0x80) == 0x00:
                    break;
                case ($c & 0x40) == 0x00:
                    return false;
                default:
                    $bit = 6;
                    do {
                        if ($bit > 25 || $i >= $inlength || (ord($in[$i]) & 0xC0) != 0x80) {
                            return false;
                        }
                        $c = ($c << 6) | (ord($in[$i++]) & 0x3F);
                        $bit += 5;
                        $mask = 1 << $bit;
                    } while ($c & $bit);
                    $c &= $mask - 1;
                    break;
            }

            // Convert and append the character to output string.
            $v = '';
            switch (true) {
                case $outsize == 4:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                case $outsize == 2:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                case $outsize == 1:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                    if ($c) {
                        return false;
                    }
                    break;
                case ($c & 0x80000000) != 0:
                    return false;
                case $c >= 0x04000000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x04000000;
                case $c >= 0x00200000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00200000;
                case $c >= 0x00010000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00010000;
                case $c >= 0x00000800:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00000800;
                case $c >= 0x00000080:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x000000C0;
                default:
                    $v .= chr($c);
                    break;
            }
            $out .= strrev($v);
        }
        return $out;
    }
}
