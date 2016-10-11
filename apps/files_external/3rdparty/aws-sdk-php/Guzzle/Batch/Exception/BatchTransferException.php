<?php

namespace Guzzle\Batch\Exception;

use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Batch\BatchTransferInterface as TransferStrategy;
use Guzzle\Batch\BatchDivisorInterface as DivisorStrategy;

/**
 * Exception thrown during a batch transfer
 */
class BatchTransferException extends \Exception implements GuzzleException
{
    /** @var array The batch being sent when the exception occurred */
    protected $batch;

    /** @var TransferStrategy The transfer strategy in use when the exception occurred */
    protected $transferStrategy;

    /** @var DivisorStrategy The divisor strategy in use when the exception occurred */
    protected $divisorStrategy;

    /** @var array Items transferred at the point in which the exception was encountered */
    protected $transferredItems;

    /**
     * @param array            $batch            The batch being sent when the exception occurred
     * @param array            $transferredItems Items transferred at the point in which the exception was encountered
     * @param \Exception       $exception        Exception encountered
     * @param TransferStrategy $transferStrategy The transfer strategy in use when the exception occurred
     * @param DivisorStrategy  $divisorStrategy  The divisor strategy in use when the exception occurred
     */
    public function __construct(
        array $batch,
        array $transferredItems,
        \Exception $exception,
        TransferStrategy $transferStrategy = null,
        DivisorStrategy $divisorStrategy = null
    ) {
        $this->batch = $batch;
        $this->transferredItems = $transferredItems;
        $this->transferStrategy = $transferStrategy;
        $this->divisorStrategy = $divisorStrategy;
        parent::__construct(
            'Exception encountered while transferring batch: ' . $exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }

    /**
     * Get the batch that we being sent when the exception occurred
     *
     * @return array
     */
    public function getBatch()
    {
        return $this->batch;
    }

    /**
     * Get the items transferred at the point in which the exception was encountered
     *
     * @return array
     */
    public function getTransferredItems()
    {
        return $this->transferredItems;
    }

    /**
     * Get the transfer strategy
     *
     * @return TransferStrategy
     */
    public function getTransferStrategy()
    {
        return $this->transferStrategy;
    }

    /**
     * Get the divisor strategy
     *
     * @return DivisorStrategy
     */
    public function getDivisorStrategy()
    {
        return $this->divisorStrategy;
    }
}
