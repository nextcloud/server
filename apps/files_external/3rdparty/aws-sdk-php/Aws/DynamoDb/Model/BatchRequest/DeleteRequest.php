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

use Aws\Common\Exception\InvalidArgumentException;
use Aws\DynamoDb\Model\Attribute;
use Guzzle\Service\Command\AbstractCommand;

/**
 * Represents a batch delete request. It is composed of a table name and key
 */
class DeleteRequest extends AbstractWriteRequest
{
    /**
     * @var array The key of the item to delete
     */
    protected $key;

    /**
     * Factory that creates a DeleteRequest from a DeleteItem command
     *
     * @param AbstractCommand $command The command to create the request from
     *
     * @return PutRequest
     *
     * @throws InvalidArgumentException if the command is not a DeleteItem command
     */
    public static function fromCommand(AbstractCommand $command)
    {
        if ($command->getName() !== 'DeleteItem') {
            throw new InvalidArgumentException();
        }

        // Get relevant data for a DeleteRequest
        $table = $command->get('TableName');
        $key   = $command->get('Key');

        // Return an instantiated DeleteRequest object
        return new DeleteRequest($key, $table);
    }

    /**
     * Constructs a new delete request
     *
     * @param array  $key       The key of the item to delete
     * @param string $tableName The name of the table which has the item
     */
    public function __construct(array $key, $tableName)
    {
        $this->key       = $key;
        $this->tableName = $tableName;
    }

    /**
     * The parameter form of the request
     *
     * @return array
     */
    public function toArray()
    {
        $key = $this->key;
        foreach ($key as &$element) {
            if ($element instanceof Attribute) {
                $element = $element->toArray();
            }
        }

        return array('DeleteRequest' => array('Key' => $key));
    }

    /**
     * Get the key
     *
     * @return array
     */
    public function getKey()
    {
        return $this->key;
    }
}
