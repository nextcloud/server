<?php

namespace Amp;

class MultiReasonException extends \Exception
{
    /** @var \Throwable[] */
    private $reasons;

    /**
     * @param \Throwable[] $reasons Array of exceptions rejecting the promise.
     * @param string|null  $message
     */
    public function __construct(array $reasons, string $message = null)
    {
        parent::__construct($message ?: "Multiple errors encountered; use "
            . self::class . "::getReasons() to retrieve the array of exceptions thrown");

        $this->reasons = $reasons;
    }

    /**
     * @return \Throwable[]
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }
}
