<?php
namespace Psalm\Issue;

use function strtolower;

abstract class MethodIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $method_id;

    public function __construct(
        string $message,
        \Psalm\CodeLocation $code_location,
        string $method_id
    ) {
        parent::__construct($message, $code_location);
        $this->method_id = strtolower($method_id);
    }
}
