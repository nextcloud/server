<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

/**
 * When a rpc call is made, the Server MUST reply with a Response, except for in the case of Notifications. The Response
 * is expressed as a single JSON Object, with the following members:
 */
abstract class Response extends Message
{
    /**
     * This member is REQUIRED. It MUST be the same as the value of the id member in the Request Object. If there was an
     * error in detecting the id in the Request object (e.g. Parse error/Invalid Request), it MUST be Null.
     *
     * @var int|string
     */
    public $id;

    /**
     * A message is considered a Response if it has an ID and either a result or an error
     *
     * @param object $msg A decoded message body
     * @return bool
     */
    public static function isResponse($msg): bool
    {
        return is_object($msg) && property_exists($msg, 'id') && (property_exists($msg, 'result') || isset($msg->error));
    }

    /**
     * @param int|string $id
     * @param mixed $result
     * @param ResponseError $error
     */
    public function __construct($id)
    {
        $this->id = $id;
    }
}
