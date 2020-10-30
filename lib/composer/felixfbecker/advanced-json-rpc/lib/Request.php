<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

/**
 * A rpc call is represented by sending a Request object to a Server
 */
class Request extends Message
{
    /**
     * An identifier established by the Client that MUST contain a String, Number, or NULL value if included. If it is
     * not included it is assumed to be a notification. The value SHOULD normally not be NULL and Numbers SHOULD NOT
     * contain fractional parts.
     *
     * @var int|string
     */
    public $id;

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
     * A message is considered a Request if it has an ID and a method.
     *
     * @param object $msg A decoded message body
     * @return bool
     */
    public static function isRequest($msg): bool
    {
        return is_object($msg) && property_exists($msg, 'id') && isset($msg->method);
    }

    /**
     * @param string|int $id
     * @param string $method
     * @param object|array $params
     */
    public function __construct($id, string $method, $params = null)
    {
        $this->id = $id;
        $this->method = $method;
        $this->params = $params;
    }
}
