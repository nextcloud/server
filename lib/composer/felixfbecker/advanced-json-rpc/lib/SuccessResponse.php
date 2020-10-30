<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

/**
 * When a rpc call is made, the Server MUST reply with a Response, except for in the case of Notifications. The Response
 * is expressed as a single JSON Object, with the following members:
 */
class SuccessResponse extends Response
{
    /**
     * This member is REQUIRED on success. This member MUST NOT exist if there was an error invoking the method. The
     * value of this member is determined by the method invoked on the Server.
     *
     * @var mixed
     */
    public $result;

    /**
     * A message is considered a SuccessResponse if it has an ID and either a result
     *
     * @param object $msg A decoded message body
     * @return bool
     */
    public static function isSuccessResponse($msg): bool
    {
        return is_object($msg) && property_exists($msg, 'id') && property_exists($msg, 'result');
    }

    /**
     * @param int|string $id
     * @param mixed $result
     */
    public function __construct($id, $result)
    {
        parent::__construct($id);
        $this->result = $result;
    }
}
