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
use Aws\Common\Enum\UaString as Ua;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Exception\UnprocessedWriteRequestsException;
use Guzzle\Batch\BatchTransferInterface;
use Guzzle\Common\Exception\ExceptionCollection;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Service\Command\CommandInterface;

/**
 * Transfer logic for executing the write request batch
 */
class WriteRequestBatchTransfer implements BatchTransferInterface
{
    /**
     * The maximum number of items allowed in a BatchWriteItem operation
     */
    const BATCH_WRITE_MAX_SIZE = 25;

    /**
     * @var AwsClientInterface The DynamoDB client for doing transfers
     */
    protected $client;

    /**
     * Constructs a transfer using the injected client
     *
     * @param AwsClientInterface $client
     */
    public function __construct(AwsClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function transfer(array $batch)
    {
        // Create a container exception for any unprocessed items
        $unprocessed = new UnprocessedWriteRequestsException();

        // Execute the transfer logic
        $this->performTransfer($batch, $unprocessed);

        // Throw an exception containing the unprocessed items if there are any
        if (count($unprocessed)) {
            throw $unprocessed;
        }
    }

    /**
     * Transfer a batch of requests and collect any unprocessed items
     *
     * @param array                             $batch               A batch of write requests
     * @param UnprocessedWriteRequestsException $unprocessedRequests Collection of unprocessed items
     *
     * @throws \Guzzle\Common\Exception\ExceptionCollection
     */
    protected function performTransfer(
        array $batch,
        UnprocessedWriteRequestsException $unprocessedRequests
    ) {
        // Do nothing if the batch is empty
        if (empty($batch)) {
            return;
        }

        // Chunk the array and prepare a set of parallel commands
        $commands = array();
        foreach (array_chunk($batch, self::BATCH_WRITE_MAX_SIZE) as $chunk) {
            // Convert the request items into the format required by the client
            $items = array();
            foreach ($chunk as $item) {
                if ($item instanceof AbstractWriteRequest) {
                    /** @var $item AbstractWriteRequest */
                    $table = $item->getTableName();
                    if (!isset($items[$table])) {
                        $items[$table] = array();
                    }
                    $items[$table][] = $item->toArray();
                }
            }

            // Create the BatchWriteItem request
            $commands[] = $this->client->getCommand('BatchWriteItem', array(
                'RequestItems' => $items,
                Ua::OPTION     => Ua::BATCH
            ));
        }

        // Execute the commands and handle exceptions
        try {
            $commands = $this->client->execute($commands);
            $this->getUnprocessedRequestsFromCommands($commands, $unprocessedRequests);
        } catch (ExceptionCollection $exceptions) {
            // Create a container exception for any unhandled (true) exceptions
            $unhandledExceptions = new ExceptionCollection();

            // Loop through caught exceptions and handle RequestTooLarge scenarios
            /** @var $e DynamoDbException */
            foreach ($exceptions as $e) {
                if ($e instanceof DynamoDbException && $e->getStatusCode() === 413) {
                    $request = $e->getResponse()->getRequest();
                    $this->retryLargeRequest($request, $unprocessedRequests);
                } else {
                    $unhandledExceptions->add($e);
                }
            }

            // If there were unhandled exceptions, throw them
            if (count($unhandledExceptions)) {
                throw $unhandledExceptions;
            }
        }
    }

    /**
     * Handles unprocessed items from the executed commands. Unprocessed items
     * can be collected and thrown in an UnprocessedWriteRequestsException
     *
     * @param array                             $commands            Array of commands
     * @param UnprocessedWriteRequestsException $unprocessedRequests Collection of unprocessed items
     */
    protected function getUnprocessedRequestsFromCommands(
        array $commands,
        UnprocessedWriteRequestsException $unprocessedRequests
    ) {
        /** @var $command CommandInterface */
        foreach ($commands as $command) {
            if ($command instanceof CommandInterface && $command->isExecuted()) {
                $result = $command->getResult();
                $items = $this->convertResultsToUnprocessedRequests($result['UnprocessedItems']);
                foreach ($items as $request) {
                    $unprocessedRequests->addItem($request);
                }
            }
        }
    }

    /**
     * Handles exceptions caused by the request being too large (over 1 MB). The
     * response will have a status code of 413. In this case the batch should be
     * split up into smaller batches and retried.
     *
     * @param EntityEnclosingRequestInterface   $request             The failed request
     * @param UnprocessedWriteRequestsException $unprocessedRequests Collection of unprocessed items
     */
    protected function retryLargeRequest(
        EntityEnclosingRequestInterface $request,
        UnprocessedWriteRequestsException $unprocessedRequests
    ) {
        // Collect the items out from the request object
        $items = json_decode($request->getBody(true), true);
        $items = $this->convertResultsToUnprocessedRequests($items['RequestItems']);

        // Divide batch into smaller batches and transfer them via recursion
        // NOTE: Dividing the batch into 3 (instead of 2) batches resulted in less recursion during testing
        if ($items) {
            $newBatches = array_chunk($items, ceil(count($items) / 3));
            foreach ($newBatches as $newBatch) {
                $this->performTransfer($newBatch, $unprocessedRequests);
            }
        }
    }

    /**
     * Collects and creates unprocessed request objects from data collected from erroneous cases
     *
     * @param array $items Data formatted under "RequestItems" or "UnprocessedItems" keys
     *
     * @return array
     */
    protected function convertResultsToUnprocessedRequests(array $items)
    {
        $unprocessed = array();
        foreach ($items as $table => $requests) {
            foreach ($requests as $request) {
                $unprocessed[] = new UnprocessedRequest($request, $table);
            }
        }

        return $unprocessed;
    }
}
