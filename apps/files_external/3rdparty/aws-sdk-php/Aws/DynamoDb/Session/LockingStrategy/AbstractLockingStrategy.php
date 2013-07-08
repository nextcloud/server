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

namespace Aws\DynamoDb\Session\LockingStrategy;

use Aws\Common\Enum\UaString as Ua;
use Aws\DynamoDb\Session\SessionHandlerConfig;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

/**
 * Base class for session locking strategies. Includes write and delete logic
 */
abstract class AbstractLockingStrategy implements LockingStrategyInterface
{
    /**
     * @var DynamoDbClient The DynamoDB client
     */
    protected $client;

    /**
     * @var SessionHandlerConfig The session handler config options
     */
    protected $config;

    /**
     * @param DynamoDbClient       $client The DynamoDB client
     * @param SessionHandlerConfig $config The session handler config options
     */
    public function __construct(DynamoDbClient $client, SessionHandlerConfig $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function doWrite($id, $data, $isDataChanged)
    {
        // Prepare the attributes
        $expires = time() + $this->config->get('session_lifetime');
        $attributes = array(
            'expires' => array(
                'Value' => array(
                    'N' => (string) $expires
                )
            )
        );
        if ($isDataChanged) {
            $attributes['data'] = array(
                'Value' => array(
                    'S' => $data
                )
            );
        }
        $attributes = array_merge($attributes, $this->getExtraAttributes());

        // Perform the UpdateItem command
        try {
            return (bool) $this->client->getCommand('UpdateItem', array(
                'TableName' => $this->config->get('table_name'),
                'Key' => $this->formatKey($id),
                'AttributeUpdates' => $attributes,
                Ua::OPTION => Ua::SESSION
            ))->execute();
        } catch (DynamoDbException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function doDestroy($id)
    {
        try {
            return (bool) $this->client->getCommand('DeleteItem', array(
                'TableName' => $this->config->get('table_name'),
                'Key' => $this->formatKey($id),
                Ua::OPTION => Ua::SESSION
            ))->execute();
        } catch (DynamoDbException $e) {
            return false;
        }
    }

    /**
     * Generates the correct key structure based on the key value and DynamoDB API version
     *
     * @param string $keyValue The value of the key (i.e., the session ID)
     *
     * @return array formatted key structure
     */
    protected function formatKey($keyValue)
    {
        $keyName = ($this->client->getApiVersion() < '2012-08-10')
            ? 'HashKeyElement'
            : $this->config->get('hash_key');

        return array($keyName => array('S' => $keyValue));
    }

    /**
     * Allows the specific strategy to add additional attributes to update
     *
     * @return array
     */
    abstract protected function getExtraAttributes();
}
