<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

/**
 * A Notification is a Request object without an "id" member. A Request object that is a Notification signifies the
 * Client's lack of interest in the corresponding Response object, and as such no Response object needs to be returned
 * to the client. The Server MUST NOT reply to a Notification, including those that are within a batch request.
 * Notifications are not confirmable by definition, since they do not have a Response object to be returned. As such,
 * the Client would not be aware of any errors (like e.g. "Invalid params","Internal error").
 */
class Notification extends Message
{
    /**
     * A String containing the name of the method to be invoked. Method names that begin with the word rpc followed by a
     * period character (U+002E or ASCII 46) are reserved for rpc-internal methods and extensions and MUST NOT be used
     * for anything else.
     *
     * @var string
     */
    public $method;

    /**
     * A Structured value that holds the parameter values to be used during the invocation of the method. This member
     * MAY be omitted. If present, parameters for the rpc call MUST be provided as a Structured value. Either
     * by-position through an Array or by-name through an Object. by-position: params MUST be an Array, containing the
     * values in the Server expected order. by-name: params MUST be an Object, with member names that match the Server
     * expected parameter names. The absence of expected names MAY result in an error being generated. The names MUST
     * match exactly, including case, to the method's expected parameters.
     *
     * @var object|array|null
     */
    public $params;

    /**
     * A message is considered a Notification if it has a method but no ID.
     *
     * @param object $msg A decoded message body
     * @return bool
     */
    public static function isNotification($msg): bool
    {
        return is_object($msg) && !property_exists($msg, 'id') && isset($msg->method);
    }

    /**
     * @param string $method
     * @param mixed $params
     */
    public function __construct(string $method, $params = null)
    {
        $this->method = $method;
        $this->params = $params;
    }
}
