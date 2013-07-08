<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\DynamoDb\Model\BatchRequest;

use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\DynamoDb\Exception\UnprocessedWriteRequestsException;
use Aws\DynamoDb\Model\Item;
use Guzzle\Batch\AbstractBatchDecorator;
use Guzzle\Batch\BatchBuilder;
use Guzzle\Batch\BatchSizeDivisor;
use Guzzle\Batch\FlushingBatch;
use Guzzle\Batch\Exception\BatchTransferException;
use Guzzle\Service\Command\AbstractCommand;

/**
 * The BatchWriteItemQueue is a BatchDecorator for Guzzle that implements a
 * queue for sending DynamoDB DeleteItem and PutItem requests. You can add
 * requests to the queue using the easy-to-use DeleteRequest and PutRequest
 * objects, or you can add DeleteItem and PutItem commands which will be
 * converted into the proper format for you. This queue attempts to send the
 * requests with the fewest service calls as possible and also re-queues any
 * unprocessed items.
 */
class WriteRequestBatch extends AbstractBatchDecorator
{
    /**
     * Factory for creating a DynamoDB BatchWriteItemQueue
     *
     * @param AwsClientInterface $client    Client used to transfer requests
     * @param int                $batchSize Size of each batch. The WriteRequestBatch works most efficiently with a
     *                                      batch size that is a multiple of 25
     * @param mixed $notify Callback to be run after each flush
     *
     * @return WriteRequestBatch
     */
    public static function factory(
        AwsClientInterface $client,
        $batchSize = WriteRequestBatchTransfer::BATCH_WRITE_MAX_SIZE,
        $notify = null
    ) {
        $builder = BatchBuilder::factory()
            ->createBatchesWith(new BatchSizeDivisor($batchSize))
            ->transferWith(new WriteRequestBatchTransfer($client));

        if ($notify) {
            $builder->notify($notify);
        }

        $batch = new self($builder->build());
        $batch = new FlushingBatch($batch, $batchSize);

        return $batch;
    }

    /**
     * {@inheritdoc}
     */
    public function add($item)
    {
        if ($item instanceof AbstractCommand) {
            // Convert PutItem and DeleteItem into the correct format
            $name = $item->getName();
            if (in_array($name, array('PutItem', 'DeleteItem'))) {
                $class = __NAMESPACE__ . '\\' . str_replace('Item', 'Request', $name);
                $item  = $class::fromCommand($item);
            } else {
                throw new InvalidArgumentException('The command provided was not a PutItem or DeleteItem command.');
            }
        }

        if (!($item instanceof WriteRequestInterface)) {
            throw new InvalidArgumentException('The item are are trying to add to the batch queue is invalid.');
        }

        return $this->decoratedBatch->add($item);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        // Flush the queue
        $items = array();
        while (!$this->decoratedBatch->isEmpty()) {
            try {
                $items = array_merge($items, $this->decoratedBatch->flush());
            } catch (BatchTransferException $e) {
                $unprocessed = $e->getPrevious();
                if ($unprocessed instanceof UnprocessedWriteRequestsException) {
                    // Handles the UnprocessedItemsException that may occur for
                    // throttled items the batch. These are re-queued here
                    foreach ($unprocessed as $unprocessedItem) {
                        $this->add($unprocessedItem);
                    }
                } else {
                    // Re-throw the exception if not handled
                    throw $e;
                }
            }
        }

        return $items;
    }
}
