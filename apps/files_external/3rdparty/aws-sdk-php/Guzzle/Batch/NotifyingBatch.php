<?php

namespace Guzzle\Batch;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * BatchInterface decorator used to call a method each time flush is called
 */
class NotifyingBatch extends AbstractBatchDecorator
{
    /** @var mixed Callable to call */
    protected $callable;

    /**
     * @param BatchInterface $decoratedBatch Batch object to decorate
     * @param mixed          $callable       Callable to call
     *
     * @throws InvalidArgumentException
     */
    public function __construct(BatchInterface $decoratedBatch, $callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('The passed argument is not callable');
        }

        $this->callable = $callable;
        parent::__construct($decoratedBatch);
    }

    public function flush()
    {
        $items = $this->decoratedBatch->flush();
        call_user_func($this->callable, $items);

        return $items;
    }
}
