<?php

namespace Guzzle\Batch;

/**
 * Interface for efficiently transferring items in a queue using batches
 */
interface BatchInterface
{
    /**
     * Add an item to the queue
     *
     * @param mixed $item Item to add
     *
     * @return self
     */
    public function add($item);

    /**
     * Flush the batch and transfer the items
     *
     * @return array Returns an array flushed items
     */
    public function flush();

    /**
     * Check if the batch is empty and has further items to transfer
     *
     * @return bool
     */
    public function isEmpty();
}
