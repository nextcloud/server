<?php

namespace Safe;

use Safe\Exceptions\XmlrpcException;

/**
 * Sets xmlrpc type, base64 or datetime, for a PHP string value.
 *
 * @param string|\DateTime $value Value to set the type
 * @param string $type 'base64' or 'datetime'
 * @throws XmlrpcException
 *
 */
function xmlrpc_set_type(&$value, string $type): void
{
    error_clear_last();
    $result = \xmlrpc_set_type($value, $type);
    if ($result === false) {
        throw XmlrpcException::createFromPhpError();
    }
}
