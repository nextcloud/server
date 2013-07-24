<?php

namespace Guzzle\Batch;

use Guzzle\Batch\BatchTransferInterface;
use Guzzle\Batch\BatchDivisorInterface;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Message\RequestInterface;

/**
 * Batch transfer strategy used to efficiently transfer a batch of requests.
 * This class is to be used with {@see Guzzle\Batch\BatchInterface}
 */
class BatchRequestTransfer implements BatchTransferInterface, BatchDivisorInterface
{
    /** @var int Size of each command batch */
    protected $batchSize;

    /**
     * Constructor used to specify how large each batch should be
     *
     * @param int $batchSize Size of each batch
     */
    public function __construct($batchSize = 50)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Creates batches of requests by grouping requests by their associated curl multi object.
     * {@inheritdoc}
     */
    public function createBatches(\SplQueue $queue)
    {
        // Create batches by client objects
        $groups = new \SplObjectStorage();
        foreach ($queue as $item) {
            if (!$item instanceof RequestInterface) {
                throw new InvalidArgumentException('All items must implement Guzzle\Http\Message\RequestInterface');
            }
            $client = $item->getClient();
            if (!$groups->contains($client)) {
                $groups->attach($client, array($item));
            } else {
                $current = $groups[$client];
                $current[] = $item;
                $groups[$client] = $current;
            }
        }

        $batches = array();
        foreach ($groups as $batch) {
            $batches = array_merge($batches, array_chunk($groups[$batch], $this->batchSize));
        }

        return $batches;
    }

    public function transfer(array $batch)
    {
        if ($batch) {
            reset($batch)->getClient()->send($batch);
        }
    }
}
