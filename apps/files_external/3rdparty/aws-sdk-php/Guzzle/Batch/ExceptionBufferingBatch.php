<?php

namespace Guzzle\Batch;

use Guzzle\Batch\Exception\BatchTransferException;

/**
 * BatchInterface decorator used to buffer exceptions encountered during a transfer.  The exceptions can then later be
 * processed after a batch flush has completed.
 */
class ExceptionBufferingBatch extends AbstractBatchDecorator
{
    /** @var array Array of BatchTransferException exceptions */
    protected $exceptions = array();

    public function flush()
    {
        $items = array();

        while (!$this->decoratedBatch->isEmpty()) {
            try {
                $transferredItems = $this->decoratedBatch->flush();
            } catch (BatchTransferException $e) {
                $this->exceptions[] = $e;
                $transferredItems = $e->getTransferredItems();
            }
            $items = array_merge($items, $transferredItems);
        }

        return $items;
    }

    /**
     * Get the buffered exceptions
     *
     * @return array Array of BatchTransferException objects
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Clear the buffered exceptions
     */
    public function clearExceptions()
    {
        $this->exceptions = array();
    }
}
