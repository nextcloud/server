<?php

namespace Guzzle\Batch;

/**
 * Divides batches into smaller batches under a certain size
 */
class BatchSizeDivisor implements BatchDivisorInterface
{
    /** @var int Size of each batch */
    protected $size;

    /** @param int $size Size of each batch */
    public function __construct($size)
    {
        $this->size = $size;
    }

    /**
     * Set the size of each batch
     *
     * @param int $size Size of each batch
     *
     * @return BatchSizeDivisor
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get the size of each batch
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    public function createBatches(\SplQueue $queue)
    {
        return array_chunk(iterator_to_array($queue, false), $this->size);
    }
}
