<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

/**
 * Base message
 */
abstract class Message
{
    /**
     * A String specifying the version of the JSON-RPC protocol. MUST be exactly "2.0".
     *
     * @var string
     */
    public $jsonrpc = '2.0';

    /**
     * Returns the appropriate Message subclass
     *
     * @param string $msg
     * @return Message
     */
    public static function parse(string $msg): Message
    {
        $decoded = json_decode($msg);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Error(json_last_error_msg(), ErrorCode::PARSE_ERROR);
        }
        if (Notification::isNotification($decoded)) {
            $obj = new Notification($decoded->method, $decoded->params ?? null);
        } else if (Request::isRequest($decoded)) {
            $obj = new Request($decoded->id, $decoded->method, $decoded->params ?? null);
        } else if (SuccessResponse::isSuccessResponse($decoded)) {
            $obj = new SuccessResponse($decoded->id, $decoded->result);
        } else if (ErrorResponse::isErrorResponse($decoded)) {
            $obj = new ErrorResponse($decoded->id, new Error($decoded->error->message, $decoded->error->code, $decoded->error->data ?? null));
        } else {
            throw new Error('Invalid message', ErrorCode::INVALID_REQUEST);
        }
        return $obj;
    }

    public function __toString(): string
    {
        $encoded = json_encode($this);
        if ($encoded === false) {
            throw new Error(json_last_error_msg(), ErrorCode::INTERNAL_ERROR);
        }
        return $encoded;
    }
}
