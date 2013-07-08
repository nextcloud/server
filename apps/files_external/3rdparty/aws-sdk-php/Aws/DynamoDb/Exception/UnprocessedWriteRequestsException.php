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

namespace Aws\DynamoDb\Exception;

use Aws\Common\Exception\RuntimeException;
use Aws\DynamoDb\Model\BatchRequest\WriteRequestInterface;

/**
 * This exception may contain unprocessed write request items
 */
class UnprocessedWriteRequestsException extends RuntimeException implements \IteratorAggregate, \Countable
{
    /**
     * @var array Unprocessed write requests
     */
    private $items = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(array $unprocessedItems = array())
    {
        parent::__construct('There were unprocessed items in the DynamoDb '
            . 'BatchWriteItem operation.');

        foreach ($unprocessedItems as $unprocessedItem) {
            $this->addItem($unprocessedItem);
        }
    }

    /**
     * Adds an unprocessed write request to the collection
     *
     * @param WriteRequestInterface $unprocessedItem
     *
     * @return UnprocessedWriteRequestsException
     */
    public function addItem(WriteRequestInterface $unprocessedItem)
    {
        $this->items[] = $unprocessedItem;

        return $this;
    }

    /**
     * Get the total number of request exceptions
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Allows array-like iteration over the request exceptions
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }
}
