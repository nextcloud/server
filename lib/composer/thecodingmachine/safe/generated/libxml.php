<?php

namespace Safe;

use Safe\Exceptions\LibxmlException;

/**
 * Retrieve last error from libxml.
 *
 * @return \LibXMLError Returns a LibXMLError object if there is any error in the
 * buffer, FALSE otherwise.
 * @throws LibxmlException
 *
 */
function libxml_get_last_error(): \LibXMLError
{
    error_clear_last();
    $result = \libxml_get_last_error();
    if ($result === false) {
        throw LibxmlException::createFromPhpError();
    }
    return $result;
}


/**
 * Changes the default external entity loader.
 *
 * @param callable $resolver_function A callable that takes three arguments. Two strings, a public id
 * and system id, and a context (an array with four keys) as the third argument.
 * This callback should return a resource, a string from which a resource can be
 * opened, or NULL.
 * @throws LibxmlException
 *
 */
function libxml_set_external_entity_loader(callable $resolver_function): void
{
    error_clear_last();
    $result = \libxml_set_external_entity_loader($resolver_function);
    if ($result === false) {
        throw LibxmlException::createFromPhpError();
    }
}
