<?php
declare(strict_types = 1);

namespace AdvancedJsonRpc;

use Exception;
use Throwable;

class Error extends Exception
{
    /**
     * A Number that indicates the error type that occurred. This MUST be an integer.
     *
     * @var int
     */
    public $code;

    /**
     * A String providing a short description of the error. The message SHOULD be limited to a concise single sentence.
     *
     * @var string
     */
    public $message;

    /**
     * A Primitive or Structured value that contains additional information about the error. This may be omitted. The
     * value of this member is defined by the Server (e.g. detailed error information, nested errors etc.).
     *
     * @var mixed
     */
    public $data;

    public function __construct(string $message, int $code, $data = null, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }
}
