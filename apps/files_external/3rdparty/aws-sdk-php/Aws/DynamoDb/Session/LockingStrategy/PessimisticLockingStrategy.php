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
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Session\SessionHandlerConfig;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Exception\ConditionalCheckFailedException;

/**
 * This locking strategy uses pessimistic locking (similar to how the native
 * PHP session handler works) to ensure that sessions are not edited while
 * another process is reading/writing to it. Pessimistic locking can be
 * expensive and can increase latencies, especially in cases where the user can
 * access the session more than once at the same time (e.g. ajax, iframes, or
 * multiple browser tabs)
 */
class PessimisticLockingStrategy extends AbstractLockingStrategy
{
    /**
     * {@inheritdoc}
     * Adds the defaults for the pessimistic locking strategy if not set
     */
    public function __construct(DynamoDbClient $client, SessionHandlerConfig $config)
    {
        $config->addDefaults(array(
            'max_lock_wait_time'       => 10,
            'min_lock_retry_microtime' => 10000,
            'max_lock_retry_microtime' => 50000,
        ));

        parent::__construct($client, $config);
    }

    /**
     * {@inheritdoc}
     * Retries the request until the lock can be acquired
     */
    public function doRead($id)
    {
        $item     = array();
        $rightNow = time();
        $timeout  = $rightNow + $this->config->get('max_lock_wait_time');

        // Create an UpdateItem command so that a lock can be set and the item
        // returned (via ReturnValues) in a single, atomic operation
        $updateItem = $this->client->getCommand('UpdateItem', array(
            'TableName' => $this->config->get('table_name'),
            'Key' => $this->formatKey($id),
            'Expected' => array(
                'lock' => array(
                    'Exists' => false
                )
            ),
            'AttributeUpdates' => array(
                'lock' => array(
                    'Value' => array(
                        'N' => '1'
                    )
                )
            ),
            'ReturnValues' => 'ALL_NEW',
            Ua::OPTION     => Ua::SESSION
        ));

        // Acquire the lock and fetch the item data
        do {
            try {
                $result = $updateItem->execute();
            } catch (ConditionalCheckFailedException $e) {
                // If lock fails, sleep and try again later
                usleep(rand(
                    $this->config->get('min_lock_retry_microtime'),
                    $this->config->get('max_lock_retry_microtime')
                ));

                $result   = array();
                $rightNow = time();
            } catch (DynamoDbException $e) {
                return $item;
            }
        } while (!$result && $rightNow < $timeout);

        // Get the item attributes
        if (isset($result['Attributes'])) {
            foreach ($result['Attributes'] as $key => $value) {
                $item[$key] = current($value);
            }
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtraAttributes()
    {
        // @codeCoverageIgnoreStart
        return array('lock' => array('Action' => 'DELETE'));
        // @codeCoverageIgnoreEnd
    }
}
