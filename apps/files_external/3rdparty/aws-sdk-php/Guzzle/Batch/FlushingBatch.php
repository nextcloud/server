<?php

namespace Guzzle\Batch;

/**
 * BatchInterface decorator used to add automatic flushing of the queue when the size of the queue reaches a threshold.
 */
class FlushingBatch extends AbstractBatchDecorator
{
    /** @var int The threshold for which to automatically flush */
    protected $threshold;

    /** @var int Current number of items known to be in the queue */
    protected $currentTotal = 0;

    /**
     * @param BatchInterface $decoratedBatch  BatchInterface that is being decorated
     * @param int            $threshold       Flush when the number in queue matches the threshold
     */
    public function __construct(BatchInterface $decoratedBatch, $threshold)
    {
        $this->threshold = $threshold;
        parent::__construct($decoratedBatch);
    }

    /**
     * Set the auto-flush threshold
     *
     * @param int $threshold The auto-flush threshold
     *
     * @return FlushingBatch
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Get the auto-flush threshold
     *
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    public function add($item)
    {
        $this->decoratedBatch->add($item);
        if (++$this->currentTotal >= $this->threshold) {
            $this->currentTotal = 0;
            $this->decoratedBatch->flush();
        }

        return $this;
    }
}
