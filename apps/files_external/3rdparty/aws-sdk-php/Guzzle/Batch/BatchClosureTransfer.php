<?php

namespace Guzzle\Batch;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * Batch transfer strategy where transfer logic can be defined via a Closure.
 * This class is to be used with {@see Guzzle\Batch\BatchInterface}
 */
class BatchClosureTransfer implements BatchTransferInterface
{
    /** @var callable A closure that performs the transfer */
    protected $callable;

    /** @var mixed $context Context passed to the callable */
    protected $context;

    /**
     * @param mixed $callable Callable that performs the transfer. This function should accept two arguments:
     *                        (array $batch, mixed $context).
     * @param mixed $context  Optional context to pass to the batch divisor
     *
     * @throws InvalidArgumentException
     */
    public function __construct($callable, $context = null)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Argument must be callable');
        }

        $this->callable = $callable;
        $this->context = $context;
    }

    public function transfer(array $batch)
    {
        return empty($batch) ? null : call_user_func($this->callable, $batch, $this->context);
    }
}
