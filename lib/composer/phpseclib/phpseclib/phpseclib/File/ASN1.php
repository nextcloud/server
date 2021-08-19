<?php

/**
 * Pure-PHP ASN.1 Parser
 *
 * PHP version 5
 *
 * ASN.1 provides the semantics for data encoded using various schemes.  The most commonly
 * utilized scheme is DER or the "Distinguished Encoding Rules".  PEM's are base64 encoded
 * DER blobs.
 *
 * \phpseclib\File\ASN1 decodes and encodes DER formatted messages and places them in a semantic context.
 *
 * Uses the 1988 ASN.1 syntax.
 *
 * @category  File
 * @package   ASN1
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2012 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib\File;

use phpseclib\File\ASN1\Element;
use phpseclib\Math\BigInteger;
use DateTime;
use DateTimeZone;

/**
 * Pure-PHP ASN.1 Parser
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
class ASN1
{
    /**#@+
     * Tag Classes
     *
     * @access private
     * @link http://www.itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#page=12
     */
    const CLASS_UNIVERSAL        = 0;
    const CLASS_APPLICATION      = 1;
    const CLASS_CONTEXT_SPECIFIC = 2;
    const CLASS_PRIVATE          = 3;
    /**#@-*/

    /**#@+
     * Tag Classes
     *
     * @access private
     * @link http://www.obj-sys.com/asn1tutorial/node124.html
    */
    const TYPE_BOOLEAN           = 1;
    const TYPE_INTEGER           = 2;
    const TYPE_BIT_STRING        = 3;
    const TYPE_OCTET_STRING      = 4;
    const TYPE_NULL              = 5;
    const TYPE_OBJECT_IDENTIFIER = 6;
    //const TYPE_OBJECT_DESCRIPTOR = 7;
    //const TYPE_INSTANCE_OF       = 8; // EXTERNAL
    const TYPE_REAL              = 9;
    const TYPE_ENUMERATED        = 10;
    //const TYPE_EMBEDDED          = 11;
    const TYPE_UTF8_STRING       = 12;
    //const TYPE_RELATIVE_OID      = 13;
    const TYPE_SEQUENCE          = 16; // SEQUENCE OF
    const TYPE_SET               = 17; // SET OF
    /**#@-*/
    /**#@+
     * More Tag Classes
     *
     * @access private
     * @link http://www.obj-sys.com/asn1tutorial/node10.html
    */
    const TYPE_NUMERIC_STRING   = 18;
    const TYPE_PRINTABLE_STRING = 19;
    const TYPE_TELETEX_STRING   = 20; // T61String
    const TYPE_VIDEOTEX_STRING  = 21;
    const TYPE_IA5_STRING       = 22;
    const TYPE_UTC_TIME         = 23;
    const TYPE_GENERALIZED_TIME = 24;
    const TYPE_GRAPHIC_STRING   = 25;
    const TYPE_VISIBLE_STRING   = 26; // ISO646String
    const TYPE_GENERAL_STRING   = 27;
    const TYPE_UNIVERSAL_STRING = 28;
    //const TYPE_CHARACTER_STRING = 29;
    const TYPE_BMP_STRING       = 30;
    /**#@-*/

    /**#@+
     * Tag Aliases
     *
     * These tags are kinda place holders for other tags.
     *
     * @access private
    */
    const TYPE_CHOICE = -1;
    const TYPE_ANY    = -2;
    /**#@-*/

    /**
     * ASN.1 object identifier
     *
     * @var array
     * @access private
     * @link http://en.wikipedia.org/wiki/Object_identifier
     */
    var $oids = array();

    /**
     * Default date format
     *
     * @var string
     * @access private
     * @link http://php.net/class.datetime
     */
    var $format = 'D, d M Y H:i:s O';

    /**
     * Default date format
     *
     * @var array
     * @access private
     * @see self::setTimeFormat()
     * @see self::asn1map()
     * @link http://php.net/class.datetime
     */
    var $encoded;

    /**
     * Filters
     *
     * If the mapping type is self::TYPE_ANY what do we actually encode it as?
     *
     * @var array
     * @access private
     * @see self::_encode_der()
     */
    var $filters;

    /**
     * Type mapping table for the ANY type.
     *
     * Structured or unknown types are mapped to a \phpseclib\File\ASN1\Element.
     * Unambiguous types get the direct mapping (int/real/bool).
     * Others are mapped as a choice, with an extra indexing level.
     *
     * @var array
     * @access public
     */
    var $ANYmap = array(
        self::TYPE_BOOLEAN              => true,
        self::TYPE_INTEGER              => true,
        self::TYPE_BIT_STRING           => 'bitString',
        self::TYPE_OCTET_STRING         => 'octetString',
        self::TYPE_NULL                 => 'null',
        self::TYPE_OBJECT_IDENTIFIER    => 'objectIdentifier',
        self::TYPE_REAL                 => true,
        self::TYPE_ENUMERATED           => 'enumerated',
        self::TYPE_UTF8_STRING          => 'utf8String',
        self::TYPE_NUMERIC_STRING       => 'numericString',
        self::TYPE_PRINTABLE_STRING     => 'printableString',
        self::TYPE_TELETEX_STRING       => 'teletexString',
        self::TYPE_VIDEOTEX_STRING      => 'videotexString',
        self::TYPE_IA5_STRING           => 'ia5String',
        self::TYPE_UTC_TIME             => 'utcTime',
        self::TYPE_GENERALIZED_TIME     => 'generalTime',
        self::TYPE_GRAPHIC_STRING       => 'graphicString',
        self::TYPE_VISIBLE_STRING       => 'visibleString',
        self::TYPE_GENERAL_STRING       => 'generalString',
        self::TYPE_UNIVERSAL_STRING     => 'universalString',
        //self::TYPE_CHARACTER_STRING     => 'characterString',
        self::TYPE_BMP_STRING           => 'bmpString'
    );

    /**
     * String type to character size mapping table.
     *
     * Non-convertable types are absent from this table.
     * size == 0 indicates variable length encoding.
     *
     * @var array
     * @access public
     */
    var $stringTypeSize = array(
        self::TYPE_UTF8_STRING      => 0,
        self::TYPE_BMP_STRING       => 2,
        self::TYPE_UNIVERSAL_STRING => 4,
        self::TYPE_PRINTABLE_STRING => 1,
        self::TYPE_TELETEX_STRING   => 1,
        self::TYPE_IA5_STRING       => 1,
        self::TYPE_VISIBLE_STRING   => 1,
    );

    /**
     * Parse BER-encoding
     *
     * Serves a similar purpose to openssl's asn1parse
     *
     * @param string $encoded
     * @return array
     * @access public
     */
    function decodeBER($encoded)
    {
        if ($encoded instanceof Element) {
            $encoded = $encoded->element;
        }

        $this->encoded = $encoded;
        // encapsulate in an array for BC with the old decodeBER
        return array($this->_decode_ber($encoded));
    }

    /**
     * Parse BER-encoding (Helper function)
     *
     * Sometimes we want to get the BER encoding of a particular tag.  $start lets us do that without having to reencode.
     * $encoded is passed by reference for the recursive calls done for self::TYPE_BIT_STRING and
     * self::TYPE_OCTET_STRING. In those cases, the indefinite length is used.
     *
     * @param string $encoded
     * @param int $start
     * @param int $encoded_pos
     * @return array
     * @access private
     */
    function _decode_ber($encoded, $start = 0, $encoded_pos = 0)
    {
        $current = array('start' => $start);

        $type = ord($encoded[$encoded_pos++]);
        $startOffset = 1;

        $constructed = ($type >> 5) & 1;

        $tag = $type & 0x1F;
        if ($tag == 0x1F) {
            $tag = 0;
            // process septets (since the eighth bit is ignored, it's not an octet)
            do {
                $temp = ord($encoded[$encoded_pos++]);
                $startOffset++;
                $loop = $temp >> 7;
                $tag <<= 7;
                $temp &= 0x7F;
                // "bits 7 to 1 of the first subsequent octet shall not all be zero"
                if ($startOffset == 2 && $temp == 0) {
                    return false;
                }
                $tag |= $temp;
            } while ($loop);
        }

        $start+= $startOffset;

        // Length, as discussed in paragraph 8.1.3 of X.690-0207.pdf#page=13
        $length = ord($encoded[$encoded_pos++]);
        $start++;
        if ($length == 0x80) { // indefinite length
            // "[A sender shall] use the indefinite form (see 8.1.3.6) if the encoding is constructed and is not all
            //  immediately available." -- paragraph 8.1.3.2.c
            $length = strlen($encoded) - $encoded_pos;
        } elseif ($length & 0x80) { // definite length, long form
            // technically, the long form of the length can be represented by up to 126 octets (bytes), but we'll only
            // support it up to four.
            $length&= 0x7F;
            $temp = substr($encoded, $encoded_pos, $length);
            $encoded_pos += $length;
            // tags of indefinte length don't really have a header length; this length includes the tag
            $current+= array('headerlength' => $length + 2);
            $start+= $length;
            extract(unpack('Nlength', substr(str_pad($temp, 4, chr(0), STR_PAD_LEFT), -4)));
        } else {
            $current+= array('headerlength' => 2);
        }

        if ($length > (strlen($encoded) - $encoded_pos)) {
            return false;
        }

        $content = substr($encoded, $encoded_pos, $length);
        $content_pos = 0;

        // at this point $length can be overwritten. it's only accurate for definite length things as is

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
            case self::CLASS_APPLICATION:
            case self::CLASS_PRIVATE:
            case self::CLASS_CONTEXT_SPECIFIC:
                if (!$constructed) {
                    return array(
                        'type'     => $class,
                        'constant' => $tag,
                        'content'  => $content,
                        'length'   => $length + $start - $current['start']
                    );
                }

                $newcontent = array();
                $remainingLength = $length;
                while ($remainingLength > 0) {
                    $temp = $this->_decode_ber($content, $start, $content_pos);
                    if ($temp === false) {
                        break;
                    }
                    $length = $temp['length'];
                    // end-of-content octets - see paragraph 8.1.5
                    if (substr($content, $content_pos + $length, 2) == "\0\0") {
                        $length+= 2;
                        $start+= $length;
                        $newcontent[] = $temp;
                        break;
                    }
                    $start+= $length;
                    $remainingLength-= $length;
                    $newcontent[] = $temp;
                    $content_pos += $length;
                }

                return array(
                    'type'     => $class,
                    'constant' => $tag,
                    // the array encapsulation is for BC with the old format
                    'content'  => $newcontent,
                    // the only time when $content['headerlength'] isn't defined is when the length is indefinite.
                    // the absence of $content['headerlength'] is how we know if something is indefinite or not.
                    // technically, it could be defined to be 2 and then another indicator could be used but whatever.
                    'length'   => $start - $current['start']
                ) + $current;
        }

        $current+= array('type' => $tag);

        // decode UNIVERSAL tags
        switch ($tag) {
            case self::TYPE_BOOLEAN:
                // "The contents octets shall consist of a single octet." -- paragraph 8.2.1
                if ($constructed || strlen($content) != 1) {
                    return false;
                }
                $current['content'] = (bool) ord($content[$content_pos]);
                break;
            case self::TYPE_INTEGER:
            case self::TYPE_ENUMERATED:
                if ($constructed) {
                    return false;
                }
                $current['content'] = new BigInteger(substr($content, $content_pos), -256);
                break;
            case self::TYPE_REAL: // not currently supported
                return false;
            case self::TYPE_BIT_STRING:
                // The initial octet shall encode, as an unsigned binary integer with bit 1 as the least significant bit,
                // the number of unused bits in the final subsequent octet. The number shall be in the range zero to
                // seven.
                if (!$constructed) {
                    $current['content'] = substr($content, $content_pos);
                } else {
                    $temp = $this->_decode_ber($content, $start, $content_pos);
                    if ($temp === false) {
                        return false;
                    }
                    $length-= (strlen($content) - $content_pos);
                    $last = count($temp) - 1;
                    for ($i = 0; $i < $last; $i++) {
                        // all subtags should be bit strings
                        if ($temp[$i]['type'] != self::TYPE_BIT_STRING) {
                            return false;
                        }
                        $current['content'].= substr($temp[$i]['content'], 1);
                    }
                    // all subtags should be bit strings
                    if ($temp[$last]['type'] != self::TYPE_BIT_STRING) {
                        return false;
                    }
                    $current['content'] = $temp[$last]['content'][0] . $current['content'] . substr($temp[$i]['content'], 1);
                }
                break;
            case self::TYPE_OCTET_STRING:
                if (!$constructed) {
                    $current['content'] = substr($content, $content_pos);
                } else {
                    $current['content'] = '';
                    $length = 0;
                    while (substr($content, $content_pos, 2) != "\0\0") {
                        $temp = $this->_decode_ber($content, $length + $start, $content_pos);
                        if ($temp === false) {
                            return false;
                        }
                        $content_pos += $temp['length'];
                        // all subtags should be octet strings
                        if ($temp['type'] != self::TYPE_OCTET_STRING) {
                            return false;
                        }
                        $current['content'].= $temp['content'];
                        $length+= $temp['length'];
                    }
                    if (substr($content, $content_pos, 2) == "\0\0") {
                        $length+= 2; // +2 for the EOC
                    }
                }
                break;
            case self::TYPE_NULL:
                // "The contents octets shall not contain any octets." -- paragraph 8.8.2
                if ($constructed || strlen($content)) {
                    return false;
                }
                break;
            case self::TYPE_SEQUENCE:
            case self::TYPE_SET:
                if (!$constructed) {
                    return false;
                }
                $offset = 0;
                $current['content'] = array();
                $content_len = strlen($content);
                while ($content_pos < $content_len) {
                    // if indefinite length construction was used and we have an end-of-content string next
                    // see paragraphs 8.1.1.3, 8.1.3.2, 8.1.3.6, 8.1.5, and (for an example) 8.6.4.2
                    if (!isset($current['headerlength']) && substr($content, $content_pos, 2) == "\0\0") {
                        $length = $offset + 2; // +2 for the EOC
                        break 2;
                    }
                    $temp = $this->_decode_ber($content, $start + $offset, $content_pos);
                    if ($temp === false) {
                        return false;
                    }
                    $content_pos += $temp['length'];
                    $current['content'][] = $temp;
                    $offset+= $temp['length'];
                }
                break;
            case self::TYPE_OBJECT_IDENTIFIER:
                if ($constructed) {
                    return false;
                }
                $current['content'] = $this->_decodeOID(substr($content, $content_pos));
                if ($current['content'] === false) {
                    return false;
                }
                break;
            /* Each character string type shall be encoded as if it had been declared:
               [UNIVERSAL x] IMPLICIT OCTET STRING

                 -- X.690-0207.pdf#page=23 (paragraph 8.21.3)

               Per that, we're not going to do any validation.  If there are any illegal characters in the string,
               we don't really care */
            case self::TYPE_NUMERIC_STRING:
                // 0,1,2,3,4,5,6,7,8,9, and space
            case self::TYPE_PRINTABLE_STRING:
                // Upper and lower case letters, digits, space, apostrophe, left/right parenthesis, plus sign, comma,
                // hyphen, full stop, solidus, colon, equal sign, question mark
            case self::TYPE_TELETEX_STRING:
                // The Teletex character set in CCITT's T61, space, and delete
                // see http://en.wikipedia.org/wiki/Teletex#Character_sets
            case self::TYPE_VIDEOTEX_STRING:
                // The Videotex character set in CCITT's T.100 and T.101, space, and delete
            case self::TYPE_VISIBLE_STRING:
                // Printing character sets of international ASCII, and space
            case self::TYPE_IA5_STRING:
                // International Alphabet 5 (International ASCII)
            case self::TYPE_GRAPHIC_STRING:
                // All registered G sets, and space
            case self::TYPE_GENERAL_STRING:
                // All registered C and G sets, space and delete
            case self::TYPE_UTF8_STRING:
                // ????
            case self::TYPE_BMP_STRING:
                if ($constructed) {
                    return false;
                }
                $current['content'] = substr($content, $content_pos);
                break;
            case self::TYPE_UTC_TIME:
            case self::TYPE_GENERALIZED_TIME:
                if ($constructed) {
                    return false;
                }
                $current['content'] = $this->_decodeTime(substr($content, $content_pos), $tag);
                break;
            default:
                return false;
        }

        $start+= $length;

        // ie. length is the length of the full TLV encoding - it's not just the length of the value
        return $current + array('length' => $start - $current['start']);
    }

    /**
     * ASN.1 Map
     *
     * Provides an ASN.1 semantic mapping ($mapping) from a parsed BER-encoding to a human readable format.
     *
     * "Special" mappings may be applied on a per tag-name basis via $special.
     *
     * @param array $decoded
     * @param array $mapping
     * @param array $special
     * @return array
     * @access public
     */
    function asn1map($decoded, $mapping, $special = array())
    {
        if (!is_array($decoded)) {
            return false;
        }

        if (isset($mapping['explicit']) && is_array($decoded['content'])) {
            $decoded = $decoded['content'][0];
        }

        switch (true) {
            case $mapping['type'] == self::TYPE_ANY:
                $intype = $decoded['type'];
                if (isset($decoded['constant']) || !isset($this->ANYmap[$intype]) || (ord($this->encoded[$decoded['start']]) & 0x20)) {
                    return new Element(substr($this->encoded, $decoded['start'], $decoded['length']));
                }
                $inmap = $this->ANYmap[$intype];
                if (is_string($inmap)) {
                    return array($inmap => $this->asn1map($decoded, array('type' => $intype) + $mapping, $special));
                }
                break;
            case $mapping['type'] == self::TYPE_CHOICE:
                foreach ($mapping['children'] as $key => $option) {
                    switch (true) {
                        case isset($option['constant']) && $option['constant'] == $decoded['constant']:
                        case !isset($option['constant']) && $option['type'] == $decoded['type']:
                            $value = $this->asn1map($decoded, $option, $special);
                            break;
                        case !isset($option['constant']) && $option['type'] == self::TYPE_CHOICE:
                            $v = $this->asn1map($decoded, $option, $special);
                            if (isset($v)) {
                                $value = $v;
                            }
                    }
                    if (isset($value)) {
                        if (isset($special[$key])) {
                            $value = call_user_func($special[$key], $value);
                        }
                        return array($key => $value);
                    }
                }
                return null;
            case isset($mapping['implicit']):
            case isset($mapping['explicit']):
            case $decoded['type'] == $mapping['type']:
                break;
            default:
                // if $decoded['type'] and $mapping['type'] are both strings, but different types of strings,
                // let it through
                switch (true) {
                    case $decoded['type'] < 18: // self::TYPE_NUMERIC_STRING == 18
                    case $decoded['type'] > 30: // self::TYPE_BMP_STRING == 30
                    case $mapping['type'] < 18:
                    case $mapping['type'] > 30:
                        return null;
                }
        }

        if (isset($mapping['implicit'])) {
            $decoded['type'] = $mapping['type'];
        }

        switch ($decoded['type']) {
            case self::TYPE_SEQUENCE:
                $map = array();

                // ignore the min and max
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $child = $mapping['children'];
                    foreach ($decoded['content'] as $content) {
                        if (($map[] = $this->asn1map($content, $child, $special)) === null) {
                            return null;
                        }
                    }

                    return $map;
                }

                $n = count($decoded['content']);
                $i = 0;

                foreach ($mapping['children'] as $key => $child) {
                    $maymatch = $i < $n; // Match only existing input.
                    if ($maymatch) {
                        $temp = $decoded['content'][$i];

                        if ($child['type'] != self::TYPE_CHOICE) {
                            // Get the mapping and input class & constant.
                            $childClass = $tempClass = self::CLASS_UNIVERSAL;
                            $constant = null;
                            if (isset($temp['constant'])) {
                                $tempClass = $temp['type'];
                            }
                            if (isset($child['class'])) {
                                $childClass = $child['class'];
                                $constant = $child['cast'];
                            } elseif (isset($child['constant'])) {
                                $childClass = self::CLASS_CONTEXT_SPECIFIC;
                                $constant = $child['constant'];
                            }

                            if (isset($constant) && isset($temp['constant'])) {
                                // Can only match if constants and class match.
                                $maymatch = $constant == $temp['constant'] && $childClass == $tempClass;
                            } else {
                                // Can only match if no constant expected and type matches or is generic.
                                $maymatch = !isset($child['constant']) && array_search($child['type'], array($temp['type'], self::TYPE_ANY, self::TYPE_CHOICE)) !== false;
                            }
                        }
                    }

                    if ($maymatch) {
                        // Attempt submapping.
                        $candidate = $this->asn1map($temp, $child, $special);
                        $maymatch = $candidate !== null;
                    }

                    if ($maymatch) {
                        // Got the match: use it.
                        if (isset($special[$key])) {
                            $candidate = call_user_func($special[$key], $candidate);
                        }
                        $map[$key] = $candidate;
                        $i++;
                    } elseif (isset($child['default'])) {
                        $map[$key] = $child['default']; // Use default.
                    } elseif (!isset($child['optional'])) {
                        return null; // Syntax error.
                    }
                }

                // Fail mapping if all input items have not been consumed.
                return $i < $n ? null: $map;

            // the main diff between sets and sequences is the encapsulation of the foreach in another for loop
            case self::TYPE_SET:
                $map = array();

                // ignore the min and max
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $child = $mapping['children'];
                    foreach ($decoded['content'] as $content) {
                        if (($map[] = $this->asn1map($content, $child, $special)) === null) {
                            return null;
                        }
                    }

                    return $map;
                }

                for ($i = 0; $i < count($decoded['content']); $i++) {
                    $temp = $decoded['content'][$i];
                    $tempClass = self::CLASS_UNIVERSAL;
                    if (isset($temp['constant'])) {
                        $tempClass = $temp['type'];
                    }

                    foreach ($mapping['children'] as $key => $child) {
                        if (isset($map[$key])) {
                            continue;
                        }
                        $maymatch = true;
                        if ($child['type'] != self::TYPE_CHOICE) {
                            $childClass = self::CLASS_UNIVERSAL;
                            $constant = null;
                            if (isset($child['class'])) {
                                $childClass = $child['class'];
                                $constant = $child['cast'];
                            } elseif (isset($child['constant'])) {
                                $childClass = self::CLASS_CONTEXT_SPECIFIC;
                                $constant = $child['constant'];
                            }

                            if (isset($constant) && isset($temp['constant'])) {
                                // Can only match if constants and class match.
                                $maymatch = $constant == $temp['constant'] && $childClass == $tempClass;
                            } else {
                                // Can only match if no constant expected and type matches or is generic.
                                $maymatch = !isset($child['constant']) && array_search($child['type'], array($temp['type'], self::TYPE_ANY, self::TYPE_CHOICE)) !== false;
                            }
                        }

                        if ($maymatch) {
                            // Attempt submapping.
                            $candidate = $this->asn1map($temp, $child, $special);
                            $maymatch = $candidate !== null;
                        }

                        if (!$maymatch) {
                            break;
                        }

                        // Got the match: use it.
                        if (isset($special[$key])) {
                            $candidate = call_user_func($special[$key], $candidate);
                        }
                        $map[$key] = $candidate;
                        break;
                    }
                }

                foreach ($mapping['children'] as $key => $child) {
                    if (!isset($map[$key])) {
                        if (isset($child['default'])) {
                            $map[$key] = $child['default'];
                        } elseif (!isset($child['optional'])) {
                            return null;
                        }
                    }
                }
                return $map;
            case self::TYPE_OBJECT_IDENTIFIER:
                return isset($this->oids[$decoded['content']]) ? $this->oids[$decoded['content']] : $decoded['content'];
            case self::TYPE_UTC_TIME:
            case self::TYPE_GENERALIZED_TIME:
                // for explicitly tagged optional stuff
                if (is_array($decoded['content'])) {
                    $decoded['content'] = $decoded['content'][0]['content'];
                }
                // for implicitly tagged optional stuff
                // in theory, doing isset($mapping['implicit']) would work but malformed certs do exist
                // in the wild that OpenSSL decodes without issue so we'll support them as well
                if (!is_object($decoded['content'])) {
                    $decoded['content'] = $this->_decodeTime($decoded['content'], $decoded['type']);
                }
                return $decoded['content'] ? $decoded['content']->format($this->format) : false;
            case self::TYPE_BIT_STRING:
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
            case self::TYPE_OCTET_STRING:
                return base64_encode($decoded['content']);
            case self::TYPE_NULL:
                return '';
            case self::TYPE_BOOLEAN:
                return $decoded['content'];
            case self::TYPE_NUMERIC_STRING:
            case self::TYPE_PRINTABLE_STRING:
            case self::TYPE_TELETEX_STRING:
            case self::TYPE_VIDEOTEX_STRING:
            case self::TYPE_IA5_STRING:
            case self::TYPE_GRAPHIC_STRING:
            case self::TYPE_VISIBLE_STRING:
            case self::TYPE_GENERAL_STRING:
            case self::TYPE_UNIVERSAL_STRING:
            case self::TYPE_UTF8_STRING:
            case self::TYPE_BMP_STRING:
                return $decoded['content'];
            case self::TYPE_INTEGER:
            case self::TYPE_ENUMERATED:
                $temp = $decoded['content'];
                if (isset($mapping['implicit'])) {
                    $temp = new BigInteger($decoded['content'], -256);
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
     * "Special" mappings can be applied via $special.
     *
     * @param string $source
     * @param string $mapping
     * @param array $special
     * @return string
     * @access public
     */
    function encodeDER($source, $mapping, $special = array())
    {
        $this->location = array();
        return $this->_encode_der($source, $mapping, null, $special);
    }

    /**
     * ASN.1 Encode (Helper function)
     *
     * @param string $source
     * @param string $mapping
     * @param int $idx
     * @param array $special
     * @return string
     * @access private
     */
    function _encode_der($source, $mapping, $idx = null, $special = array())
    {
        if ($source instanceof Element) {
            return $source->element;
        }

        // do not encode (implicitly optional) fields with value set to default
        if (isset($mapping['default']) && $source === $mapping['default']) {
            return '';
        }

        if (isset($idx)) {
            if (isset($special[$idx])) {
                $source = call_user_func($special[$idx], $source);
            }
            $this->location[] = $idx;
        }

        $tag = $mapping['type'];

        switch ($tag) {
            case self::TYPE_SET:    // Children order is not important, thus process in sequence.
            case self::TYPE_SEQUENCE:
                $tag|= 0x20; // set the constructed bit

                // ignore the min and max
                if (isset($mapping['min']) && isset($mapping['max'])) {
                    $value = array();
                    $child = $mapping['children'];

                    foreach ($source as $content) {
                        $temp = $this->_encode_der($content, $child, null, $special);
                        if ($temp === false) {
                            return false;
                        }
                        $value[]= $temp;
                    }
                    /* "The encodings of the component values of a set-of value shall appear in ascending order, the encodings being compared
                        as octet strings with the shorter components being padded at their trailing end with 0-octets.
                        NOTE - The padding octets are for comparison purposes only and do not appear in the encodings."

                       -- sec 11.6 of http://www.itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf  */
                    if ($mapping['type'] == self::TYPE_SET) {
                        sort($value);
                    }
                    $value = implode('', $value);
                    break;
                }

                $value = '';
                foreach ($mapping['children'] as $key => $child) {
                    if (!array_key_exists($key, $source)) {
                        if (!isset($child['optional'])) {
                            return false;
                        }
                        continue;
                    }

                    $temp = $this->_encode_der($source[$key], $child, $key, $special);
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
                        if (isset($child['explicit']) || $child['type'] == self::TYPE_CHOICE) {
                            $subtag = chr((self::CLASS_CONTEXT_SPECIFIC << 6) | 0x20 | $child['constant']);
                            $temp = $subtag . $this->_encodeLength(strlen($temp)) . $temp;
                        } else {
                            $subtag = chr((self::CLASS_CONTEXT_SPECIFIC << 6) | (ord($temp[0]) & 0x20) | $child['constant']);
                            $temp = $subtag . substr($temp, 1);
                        }
                    }
                    $value.= $temp;
                }
                break;
            case self::TYPE_CHOICE:
                $temp = false;

                foreach ($mapping['children'] as $key => $child) {
                    if (!isset($source[$key])) {
                        continue;
                    }

                    $temp = $this->_encode_der($source[$key], $child, $key, $special);
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
                        if (isset($child['explicit']) || $child['type'] == self::TYPE_CHOICE) {
                            $subtag = chr((self::CLASS_CONTEXT_SPECIFIC << 6) | 0x20 | $child['constant']);
                            $temp = $subtag . $this->_encodeLength(strlen($temp)) . $temp;
                        } else {
                            $subtag = chr((self::CLASS_CONTEXT_SPECIFIC << 6) | (ord($temp[0]) & 0x20) | $child['constant']);
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
            case self::TYPE_INTEGER:
            case self::TYPE_ENUMERATED:
                if (!isset($mapping['mapping'])) {
                    if (is_numeric($source)) {
                        $source = new BigInteger($source);
                    }
                    $value = $source->toBytes(true);
                } else {
                    $value = array_search($source, $mapping['mapping']);
                    if ($value === false) {
                        return false;
                    }
                    $value = new BigInteger($value);
                    $value = $value->toBytes(true);
                }
                if (!strlen($value)) {
                    $value = chr(0);
                }
                break;
            case self::TYPE_UTC_TIME:
            case self::TYPE_GENERALIZED_TIME:
                $format = $mapping['type'] == self::TYPE_UTC_TIME ? 'y' : 'Y';
                $format.= 'mdHis';
                // if $source does _not_ include timezone information within it then assume that the timezone is GMT
                $date = new DateTime($source, new DateTimeZone('GMT'));
                // if $source _does_ include timezone information within it then convert the time to GMT
                $date->setTimezone(new DateTimeZone('GMT'));
                $value = $date->format($format) . 'Z';
                break;
            case self::TYPE_BIT_STRING:
                if (isset($mapping['mapping'])) {
                    $bits = array_fill(0, count($mapping['mapping']), 0);
                    $size = 0;
                    for ($i = 0; $i < count($mapping['mapping']); $i++) {
                        if (in_array($mapping['mapping'][$i], $source)) {
                            $bits[$i] = 1;
                            $size = $i;
                        }
                    }

                    if (isset($mapping['min']) && $mapping['min'] >= 1 && $size < $mapping['min']) {
                        $size = $mapping['min'] - 1;
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
            case self::TYPE_OCTET_STRING:
                /* The initial octet shall encode, as an unsigned binary integer with bit 1 as the least significant bit,
                   the number of unused bits in the final subsequent octet. The number shall be in the range zero to seven.

                   -- http://www.itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#page=16 */
                $value = base64_decode($source);
                break;
            case self::TYPE_OBJECT_IDENTIFIER:
                $value = $this->_encodeOID($source);
                break;
            case self::TYPE_ANY:
                $loc = $this->location;
                if (isset($idx)) {
                    array_pop($this->location);
                }

                switch (true) {
                    case !isset($source):
                        return $this->_encode_der(null, array('type' => self::TYPE_NULL) + $mapping, null, $special);
                    case is_int($source):
                    case $source instanceof BigInteger:
                        return $this->_encode_der($source, array('type' => self::TYPE_INTEGER) + $mapping, null, $special);
                    case is_float($source):
                        return $this->_encode_der($source, array('type' => self::TYPE_REAL) + $mapping, null, $special);
                    case is_bool($source):
                        return $this->_encode_der($source, array('type' => self::TYPE_BOOLEAN) + $mapping, null, $special);
                    case is_array($source) && count($source) == 1:
                        $typename = implode('', array_keys($source));
                        $outtype = array_search($typename, $this->ANYmap, true);
                        if ($outtype !== false) {
                            return $this->_encode_der($source[$typename], array('type' => $outtype) + $mapping, null, $special);
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
                    user_error('No filters defined for ' . implode('/', $loc));
                    return false;
                }
                return $this->_encode_der($source, $filters + $mapping, null, $special);
            case self::TYPE_NULL:
                $value = '';
                break;
            case self::TYPE_NUMERIC_STRING:
            case self::TYPE_TELETEX_STRING:
            case self::TYPE_PRINTABLE_STRING:
            case self::TYPE_UNIVERSAL_STRING:
            case self::TYPE_UTF8_STRING:
            case self::TYPE_BMP_STRING:
            case self::TYPE_IA5_STRING:
            case self::TYPE_VISIBLE_STRING:
            case self::TYPE_VIDEOTEX_STRING:
            case self::TYPE_GRAPHIC_STRING:
            case self::TYPE_GENERAL_STRING:
                $value = $source;
                break;
            case self::TYPE_BOOLEAN:
                $value = $source ? "\xFF" : "\x00";
                break;
            default:
                user_error('Mapping provides no type definition for ' . implode('/', $this->location));
                return false;
        }

        if (isset($idx)) {
            array_pop($this->location);
        }

        if (isset($mapping['cast'])) {
            if (isset($mapping['explicit']) || $mapping['type'] == self::TYPE_CHOICE) {
                $value = chr($tag) . $this->_encodeLength(strlen($value)) . $value;
                $tag = ($mapping['class'] << 6) | 0x20 | $mapping['cast'];
            } else {
                $tag = ($mapping['class'] << 6) | (ord($temp[0]) & 0x20) | $mapping['cast'];
            }
        }

        return chr($tag) . $this->_encodeLength(strlen($value)) . $value;
    }

    /**
     * DER-encode the length
     *
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.  See
     * {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 paragraph 8.1.3} for more information.
     *
     * @access private
     * @param int $length
     * @return string
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
     * BER-decode the OID
     *
     * Called by _decode_ber()
     *
     * @access private
     * @param string $content
     * @return string
     */
    function _decodeOID($content)
    {
        static $eighty;
        if (!$eighty) {
            $eighty = new BigInteger(80);
        }

        $oid = array();
        $pos = 0;
        $len = strlen($content);

        if (ord($content[$len - 1]) & 0x80) {
            return false;
        }

        $n = new BigInteger();
        while ($pos < $len) {
            $temp = ord($content[$pos++]);
            $n = $n->bitwise_leftShift(7);
            $n = $n->bitwise_or(new BigInteger($temp & 0x7F));
            if (~$temp & 0x80) {
                $oid[] = $n;
                $n = new BigInteger();
            }
        }
        $part1 = array_shift($oid);
        $first = floor(ord($content[0]) / 40);
        /*
          "This packing of the first two object identifier components recognizes that only three values are allocated from the root
           node, and at most 39 subsequent values from nodes reached by X = 0 and X = 1."

          -- https://www.itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#page=22
        */
        if ($first <= 2) { // ie. 0 <= ord($content[0]) < 120 (0x78)
            array_unshift($oid, ord($content[0]) % 40);
            array_unshift($oid, $first);
        } else {
            array_unshift($oid, $part1->subtract($eighty));
            array_unshift($oid, 2);
        }

        return implode('.', $oid);
    }

    /**
     * DER-encode the OID
     *
     * Called by _encode_der()
     *
     * @access private
     * @param string $source
     * @return string
     */
    function _encodeOID($source)
    {
        static $mask, $zero, $forty;
        if (!$mask) {
            $mask = new BigInteger(0x7F);
            $zero = new BigInteger();
            $forty = new BigInteger(40);
        }

        $oid = preg_match('#(?:\d+\.)+#', $source) ? $source : array_search($source, $this->oids);
        if ($oid === false) {
            user_error('Invalid OID');
            return false;
        }
        $parts = explode('.', $oid);
        $part1 = array_shift($parts);
        $part2 = array_shift($parts);

        $first = new BigInteger($part1);
        $first = $first->multiply($forty);
        $first = $first->add(new BigInteger($part2));

        array_unshift($parts, $first->toString());

        $value = '';
        foreach ($parts as $part) {
            if (!$part) {
                $temp = "\0";
            } else {
                $temp = '';
                $part = new BigInteger($part);
                while (!$part->equals($zero)) {
                    $submask = $part->bitwise_and($mask);
                    $submask->setPrecision(8);
                    $temp = (chr(0x80) | $submask->toBytes()) . $temp;
                    $part = $part->bitwise_rightShift(7);
                }
                $temp[strlen($temp) - 1] = $temp[strlen($temp) - 1] & chr(0x7F);
            }
            $value.= $temp;
        }

        return $value;
    }

    /**
     * BER-decode the time
     *
     * Called by _decode_ber() and in the case of implicit tags asn1map().
     *
     * @access private
     * @param string $content
     * @param int $tag
     * @return string
     */
    function _decodeTime($content, $tag)
    {
        /* UTCTime:
           http://tools.ietf.org/html/rfc5280#section-4.1.2.5.1
           http://www.obj-sys.com/asn1tutorial/node15.html

           GeneralizedTime:
           http://tools.ietf.org/html/rfc5280#section-4.1.2.5.2
           http://www.obj-sys.com/asn1tutorial/node14.html */

        $format = 'YmdHis';

        if ($tag == self::TYPE_UTC_TIME) {
            // https://www.itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#page=28 says "the seconds
            // element shall always be present" but none-the-less I've seen X509 certs where it isn't and if the
            // browsers parse it phpseclib ought to too
            if (preg_match('#^(\d{10})(Z|[+-]\d{4})$#', $content, $matches)) {
                $content = $matches[1] . '00' . $matches[2];
            }
            $prefix = substr($content, 0, 2) >= 50 ? '19' : '20';
            $content = $prefix . $content;
        } elseif (strpos($content, '.') !== false) {
            $format.= '.u';
        }

        if ($content[strlen($content) - 1] == 'Z') {
            $content = substr($content, 0, -1) . '+0000';
        }

        if (strpos($content, '-') !== false || strpos($content, '+') !== false) {
            $format.= 'O';
        }

        // error supression isn't necessary as of PHP 7.0:
        // http://php.net/manual/en/migration70.other-changes.php
        return @DateTime::createFromFormat($format, $content);
    }

    /**
     * Set the time format
     *
     * Sets the time / date format for asn1map().
     *
     * @access public
     * @param string $format
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
     * @param array $oids
     */
    function loadOIDs($oids)
    {
        $this->oids = $oids;
    }

    /**
     * Load filters
     *
     * See \phpseclib\File\X509, etc, for an example.
     *
     * @access public
     * @param array $filters
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
     * @param string $string
     * @param int $index
     * @return string
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
     * @param string $in
     * @param int $from
     * @param int $to
     * @return string
     * @access public
     */
    function convert($in, $from = self::TYPE_UTF8_STRING, $to = self::TYPE_UTF8_STRING)
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
