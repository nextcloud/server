<?php

namespace Guzzle\Batch;

/**
 * Interface used for dividing a queue of items into an array of batches
 */
interface BatchDivisorInterface
{
    /**
     * Divide a queue of items into an array batches
     *
     * @param \SplQueue $queue Queue of items to divide into batches. Items are removed as they are iterated.
     *
     * @return array|\Traversable Returns an array or Traversable object that contains arrays of items to transfer
     */
    public function createBatches(\SplQueue $queue);
}
