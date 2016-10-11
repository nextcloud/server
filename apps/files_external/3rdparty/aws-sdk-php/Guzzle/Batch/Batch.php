<?php

namespace Guzzle\Batch;

use Guzzle\Batch\Exception\BatchTransferException;

/**
 * Default batch implementation used to convert queued items into smaller chunks of batches using a
 * {@see BatchDivisorIterface} and transfers each batch using a {@see BatchTransferInterface}.
 *
 * Any exception encountered during a flush operation will throw a {@see BatchTransferException} object containing the
 * batch that failed. After an exception is encountered, you can flush the batch again to attempt to finish transferring
 * any previously created batches or queued items.
 */
class Batch implements BatchInterface
{
    /** @var \SplQueue Queue of items in the queue */
    protected $queue;

    /** @var array Divided batches to be transferred */
    protected $dividedBatches;

    /** @var BatchTransferInterface */
    protected $transferStrategy;

    /** @var BatchDivisorInterface */
    protected $divisionStrategy;

    /**
     * @param BatchTransferInterface $transferStrategy Strategy used to transfer items
     * @param BatchDivisorInterface  $divisionStrategy Divisor used to create batches
     */
    public function __construct(BatchTransferInterface $transferStrategy, BatchDivisorInterface $divisionStrategy)
    {
        $this->transferStrategy = $transferStrategy;
        $this->divisionStrategy = $divisionStrategy;
        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);
        $this->dividedBatches = array();
    }

    public function add($item)
    {
        $this->queue->enqueue($item);

        return $this;
    }

    public function flush()
    {
        $this->createBatches();

        $items = array();
        foreach ($this->dividedBatches as $batchIndex => $dividedBatch) {
            while ($dividedBatch->valid()) {
                $batch = $dividedBatch->current();
                $dividedBatch->next();
                try {
                    $this->transferStrategy->transfer($batch);
                    $items = array_merge($items, $batch);
                } catch (\Exception $e) {
                    throw new BatchTransferException($batch, $items, $e, $this->transferStrategy, $this->divisionStrategy);
                }
            }
            // Keep the divided batch down to a minimum in case of a later exception
            unset($this->dividedBatches[$batchIndex]);
        }

        return $items;
    }

    public function isEmpty()
    {
        return count($this->queue) == 0 && count($this->dividedBatches) == 0;
    }

    /**
     * Create batches for any queued items
     */
    protected function createBatches()
    {
        if (count($this->queue)) {
            if ($batches = $this->divisionStrategy->createBatches($this->queue)) {
                // Convert arrays into iterators
                if (is_array($batches)) {
                    $batches = new \ArrayIterator($batches);
                }
                $this->dividedBatches[] = $batches;
            }
        }
    }
}
