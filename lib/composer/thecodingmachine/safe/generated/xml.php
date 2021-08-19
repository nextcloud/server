<?php

namespace Safe;

use Safe\Exceptions\XmlException;

/**
 * xml_parser_create_ns creates a new XML parser
 * with XML namespace support and returns a resource handle referencing
 * it to be used by the other XML functions.
 *
 * @param string $encoding The input encoding is automatically detected, so that the
 * encoding parameter specifies only the output
 * encoding. In PHP 5.0.0 and 5.0.1, the default output charset is
 * ISO-8859-1, while in PHP 5.0.2 and upper is UTF-8. The supported
 * encodings are ISO-8859-1, UTF-8 and
 * US-ASCII.
 * @param string $separator With a namespace aware parser tag parameters passed to the various
 * handler functions will consist of namespace and tag name separated by
 * the string specified in separator.
 * @return resource Returns a resource handle for the new XML parser.
 * @throws XmlException
 *
 */
function xml_parser_create_ns(string $encoding = null, string $separator = ":")
{
    error_clear_last();
    if ($separator !== ":") {
        $result = \xml_parser_create_ns($encoding, $separator);
    } elseif ($encoding !== null) {
        $result = \xml_parser_create_ns($encoding);
    } else {
        $result = \xml_parser_create_ns();
    }
    if ($result === false) {
        throw XmlException::createFromPhpError();
    }
    return $result;
}


/**
 * xml_parser_create creates a new XML parser
 * and returns a resource handle referencing it to be used by the
 * other XML functions.
 *
 * @param string $encoding The optional encoding specifies the character
 * encoding for the input/output in PHP 4. Starting from PHP 5, the input
 * encoding is automatically detected, so that the
 * encoding parameter specifies only the output
 * encoding. In PHP 4, the default output encoding is the same as the
 * input charset. If empty string is passed, the parser attempts to identify
 * which encoding the document is encoded in by looking at the heading 3 or
 * 4 bytes. In PHP 5.0.0 and 5.0.1, the default output charset is
 * ISO-8859-1, while in PHP 5.0.2 and upper is UTF-8. The supported
 * encodings are ISO-8859-1, UTF-8 and
 * US-ASCII.
 * @return resource Returns a resource handle for the new XML parser.
 * @throws XmlException
 *
 */
function xml_parser_create(string $encoding = null)
{
    error_clear_last();
    if ($encoding !== null) {
        $result = \xml_parser_create($encoding);
    } else {
        $result = \xml_parser_create();
    }
    if ($result === false) {
        throw XmlException::createFromPhpError();
    }
    return $result;
}


/**
 * This function allows to use parser inside
 * object. All callback functions could be set with
 * xml_set_element_handler etc and assumed to be
 * methods of object.
 *
 * @param resource $parser A reference to the XML parser to use inside the object.
 * @param object $object The object where to use the XML parser.
 * @throws XmlException
 *
 */
function xml_set_object($parser, object &$object): void
{
    error_clear_last();
    $result = \xml_set_object($parser, $object);
    if ($result === false) {
        throw XmlException::createFromPhpError();
    }
}
