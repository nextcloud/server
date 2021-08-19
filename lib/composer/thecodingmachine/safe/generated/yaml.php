<?php

namespace Safe;

use Safe\Exceptions\YamlException;

/**
 * Convert all or part of a YAML document stream read from a file to a PHP variable.
 *
 * @param string $filename Path to the file.
 * @param int $pos Document to extract from stream (-1 for all
 * documents, 0 for first document, ...).
 * @param int|null $ndocs If ndocs is provided, then it is filled with the
 * number of documents found in stream.
 * @param array $callbacks Content handlers for YAML nodes. Associative array of YAML
 * tag =&gt; callable mappings. See
 * parse callbacks for more
 * details.
 * @return mixed Returns the value encoded in input in appropriate
 * PHP type. If pos is -1 an
 * array will be returned with one entry for each document found
 * in the stream.
 * @throws YamlException
 *
 */
function yaml_parse_file(string $filename, int $pos = 0, ?int &$ndocs = null, array $callbacks = null)
{
    error_clear_last();
    $result = \yaml_parse_file($filename, $pos, $ndocs, $callbacks);
    if ($result === false) {
        throw YamlException::createFromPhpError();
    }
    return $result;
}


/**
 * Convert all or part of a YAML document stream read from a URL to a PHP variable.
 *
 * @param string $url url should be of the form "scheme://...". PHP
 * will search for a protocol handler (also known as a wrapper) for that
 * scheme. If no wrappers for that protocol are registered, PHP will emit
 * a notice to help you track potential problems in your script and then
 * continue as though filename specifies a regular file.
 * @param int $pos Document to extract from stream (-1 for all
 * documents, 0 for first document, ...).
 * @param int|null $ndocs If ndocs is provided, then it is filled with the
 * number of documents found in stream.
 * @param array $callbacks Content handlers for YAML nodes. Associative array of YAML
 * tag =&gt; callable mappings. See
 * parse callbacks for more
 * @return mixed Returns the value encoded in input in appropriate
 * PHP type. If pos is
 * -1 an array will be returned with one entry
 * for each document found in the stream.
 * @throws YamlException
 *
 */
function yaml_parse_url(string $url, int $pos = 0, ?int &$ndocs = null, array $callbacks = null)
{
    error_clear_last();
    $result = \yaml_parse_url($url, $pos, $ndocs, $callbacks);
    if ($result === false) {
        throw YamlException::createFromPhpError();
    }
    return $result;
}


/**
 * Convert all or part of a YAML document stream to a PHP variable.
 *
 * @param string $input The string to parse as a YAML document stream.
 * @param int $pos Document to extract from stream (-1 for all
 * documents, 0 for first document, ...).
 * @param int|null $ndocs If ndocs is provided, then it is filled with the
 * number of documents found in stream.
 * @param array $callbacks Content handlers for YAML nodes. Associative array of YAML
 * tag =&gt; callable mappings. See
 * parse callbacks for more
 * details.
 * @return mixed Returns the value encoded in input in appropriate
 * PHP type. If pos is -1 an
 * array will be returned with one entry for each document found
 * in the stream.
 * @throws YamlException
 *
 */
function yaml_parse(string $input, int $pos = 0, ?int &$ndocs = null, array $callbacks = null)
{
    error_clear_last();
    $result = \yaml_parse($input, $pos, $ndocs, $callbacks);
    if ($result === false) {
        throw YamlException::createFromPhpError();
    }
    return $result;
}
