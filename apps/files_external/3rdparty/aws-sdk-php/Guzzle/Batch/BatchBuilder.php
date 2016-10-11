<?php

namespace Guzzle\Batch;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\RuntimeException;

/**
 * Builder used to create custom batch objects
 */
class BatchBuilder
{
    /** @var bool Whether or not the batch should automatically flush*/
    protected $autoFlush = false;

    /** @var bool Whether or not to maintain a batch history */
    protected $history = false;

    /** @var bool Whether or not to buffer exceptions encountered in transfer */
    protected $exceptionBuffering = false;

    /** @var mixed Callable to invoke each time a flush completes */
    protected $afterFlush;

    /** @var BatchTransferInterface Object used to transfer items in the queue */
    protected $transferStrategy;

    /** @var BatchDivisorInterface Object used to divide the queue into batches */
    protected $divisorStrategy;

    /** @var array of Mapped transfer strategies by handle name */
    protected static $mapping = array(
        'request' => 'Guzzle\Batch\BatchRequestTransfer',
        'command' => 'Guzzle\Batch\BatchCommandTransfer'
    );

    /**
     * Create a new instance of the BatchBuilder
     *
     * @return BatchBuilder
     */
    public static function factory()
    {
        return new self();
    }

    /**
     * Automatically flush the batch when the size of the queue reaches a certain threshold. Adds {@see FlushingBatch}.
     *
     * @param $threshold Number of items to allow in the queue before a flush
     *
     * @return BatchBuilder
     */
    public function autoFlushAt($threshold)
    {
        $this->autoFlush = $threshold;

        return $this;
    }

    /**
     * Maintain a history of all items that have been transferred using the batch. Adds {@see HistoryBatch}.
     *
     * @return BatchBuilder
     */
    public function keepHistory()
    {
        $this->history = true;

        return $this;
    }

    /**
     * Buffer exceptions thrown during transfer so that you can transfer as much as possible, and after a transfer
     * completes, inspect each exception that was thrown. Enables the {@see ExceptionBufferingBatch} decorator.
     *
     * @return BatchBuilder
     */
    public function bufferExceptions()
    {
        $this->exceptionBuffering = true;

        return $this;
    }

    /**
     * Notify a callable each time a batch flush completes. Enables the {@see NotifyingBatch} decorator.
     *
     * @param mixed $callable Callable function to notify
     *
     * @return BatchBuilder
     * @throws InvalidArgumentException if the argument is not callable
     */
    public function notify($callable)
    {
        $this->afterFlush = $callable;

        return $this;
    }

    /**
     * Configures the batch to transfer batches of requests. Associates a {@see \Guzzle\Http\BatchRequestTransfer}
     * object as both the transfer and divisor strategy.
     *
     * @param int $batchSize Batch size for each batch of requests
     *
     * @return BatchBuilder
     */
    public function transferRequests($batchSize = 50)
    {
        $className = self::$mapping['request'];
        $this->transferStrategy = new $className($batchSize);
        $this->divisorStrategy = $this->transferStrategy;

        return $this;
    }

    /**
     * Configures the batch to transfer batches commands. Associates as
     * {@see \Guzzle\Service\Command\BatchCommandTransfer} as both the transfer and divisor strategy.
     *
     * @param int $batchSize Batch size for each batch of commands
     *
     * @return BatchBuilder
     */
    public function transferCommands($batchSize = 50)
    {
        $className = self::$mapping['command'];
        $this->transferStrategy = new $className($batchSize);
        $this->divisorStrategy = $this->transferStrategy;

        return $this;
    }

    /**
     * Specify the strategy used to divide the queue into an array of batches
     *
     * @param BatchDivisorInterface $divisorStrategy Strategy used to divide a batch queue into batches
     *
     * @return BatchBuilder
     */
    public function createBatchesWith(BatchDivisorInterface $divisorStrategy)
    {
        $this->divisorStrategy = $divisorStrategy;

        return $this;
    }

    /**
     * Specify the strategy used to transport the items when flush is called
     *
     * @param BatchTransferInterface $transferStrategy How items are transferred
     *
     * @return BatchBuilder
     */
    public function transferWith(BatchTransferInterface $transferStrategy)
    {
        $this->transferStrategy = $transferStrategy;

        return $this;
    }

    /**
     * Create and return the instantiated batch
     *
     * @return BatchInterface
     * @throws RuntimeException if no transfer strategy has been specified
     */
    public function build()
    {
        if (!$this->transferStrategy) {
            throw new RuntimeException('No transfer strategy has been specified');
        }

        if (!$this->divisorStrategy) {
            throw new RuntimeException('No divisor strategy has been specified');
        }

        $batch = new Batch($this->transferStrategy, $this->divisorStrategy);

        if ($this->exceptionBuffering) {
            $batch = new ExceptionBufferingBatch($batch);
        }

        if ($this->afterFlush) {
            $batch = new NotifyingBatch($batch, $this->afterFlush);
        }

        if ($this->autoFlush) {
            $batch = new FlushingBatch($batch, $this->autoFlush);
        }

        if ($this->history) {
            $batch = new HistoryBatch($batch);
        }

        return $batch;
    }
}
