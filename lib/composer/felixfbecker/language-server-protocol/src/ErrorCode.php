<?php

namespace LanguageServerProtocol;

/**
 * Enum
 */
abstract class ErrorCode
{
    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;
    const INTERNAL_ERROR = -32603;
    const SERVER_ERROR_START = -32099;
    const SERVER_ERROR_END = -32000;
}
