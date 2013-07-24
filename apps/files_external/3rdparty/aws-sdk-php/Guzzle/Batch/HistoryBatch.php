<?php

namespace Guzzle\Batch;

/**
 * BatchInterface decorator used to keep a history of items that were added to the batch.  You must clear the history
 * manually to remove items from the history.
 */
class HistoryBatch extends AbstractBatchDecorator
{
    /** @var array Items in the history */
    protected $history = array();

    public function add($item)
    {
        $this->history[] = $item;
        $this->decoratedBatch->add($item);

        return $this;
    }

    /**
     * Get the batch history
     *
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Clear the batch history
     */
    public function clearHistory()
    {
        $this->history = array();
    }
}
