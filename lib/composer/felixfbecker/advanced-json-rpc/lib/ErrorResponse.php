<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

/**
 * When a rpc call is made, the Server MUST reply with a Response, except for in the case of Notifications. The Response
 * is expressed as a single JSON Object, with the following members:
 */
class ErrorResponse extends Response
{
    /**
     * This member is REQUIRED on error. This member MUST NOT exist if there was no error triggered during invocation.
     * The value for this member MUST be an Object as defined in section 5.1.
     *
     * @var \AdvancedJsonRpc\Error
     */
    public $error;

    /**
     * A message is considered a Response if it has an ID and either a result or an error
     *
     * @param object $msg A decoded message body
     * @return bool
     */
    public static function isErrorResponse($msg): bool
    {
        return is_object($msg) && isset($msg->id) && isset($msg->error);
    }

    /**
     * @param int|string $id
     * @param \AdvancedJsonRpc\Error $error
     */
    public function __construct($id, Error $error)
    {
        parent::__construct($id);
        $this->error = $error;
    }
}
