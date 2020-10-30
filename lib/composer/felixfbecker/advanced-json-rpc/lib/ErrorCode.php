<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

/**
 * The error codes from and including -32768 to -32000 are reserved for pre-defined errors. Any code within this range,
 * but not defined explicitly below is reserved for future use. The error codes are nearly the same as those suggested
 * for XML-RPC at the following url: http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php
 * The remainder of the space is available for application defined errors.
 */
abstract class ErrorCode
{
    /**
     * Invalid JSON was received by the server. An error occurred on the server while parsing the JSON text.
     */
    const PARSE_ERROR = -32700;

    /**
     * The JSON sent is not a valid Request object.
     */
    const INVALID_REQUEST = -32600;

    /**
     * The method does not exist / is not available.
     */
    const METHOD_NOT_FOUND = -32601;

    /**
     * Invalid method parameter(s).
     */
    const INVALID_PARAMS = -32602;

    /**
     * Internal JSON-RPC error.
     */
    const INTERNAL_ERROR = -32603;

    /**
     * Reserved for implementation-defined server-errors.
     */
    const SERVER_ERROR_START = -32099;

    /**
     * Reserved for implementation-defined server-errors.
     */
    const SERVER_ERROR_END = -32000;
}
