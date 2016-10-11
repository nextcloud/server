<?php

namespace Guzzle\Batch;

/**
 * Abstract decorator used when decorating a BatchInterface
 */
abstract class AbstractBatchDecorator implements BatchInterface
{
    /** @var BatchInterface Decorated batch object */
    protected $decoratedBatch;

    /**
     * @param BatchInterface $decoratedBatch  BatchInterface that is being decorated
     */
    public function __construct(BatchInterface $decoratedBatch)
    {
        $this->decoratedBatch = $decoratedBatch;
    }

    /**
     * Allow decorators to implement custom methods
     *
     * @param string $method Missing method name
     * @param array  $args   Method arguments
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public function __call($method, array $args)
    {
        return call_user_func_array(array($this->decoratedBatch, $method), $args);
    }

    public function add($item)
    {
        $this->decoratedBatch->add($item);

        return $this;
    }

    public function flush()
    {
        return $this->decoratedBatch->flush();
    }

    public function isEmpty()
    {
        return $this->decoratedBatch->isEmpty();
    }

    /**
     * Trace the decorators associated with the batch
     *
     * @return array
     */
    public function getDecorators()
    {
        $found = array($this);
        if (method_exists($this->decoratedBatch, 'getDecorators')) {
            $found = array_merge($found, $this->decoratedBatch->getDecorators());
        }

        return $found;
    }
}
