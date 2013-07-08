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
use Aws\DynamoDb\Exception\DynamoDbException;

/**
 * The NULL locking strategy is the default strategy that does NOT do session
 * locking. Session locking can cause extra latency and costs when retrying
 * lock acquisitions. Thus, the null strategy is the most reasonable default.
 */
class NullLockingStrategy extends AbstractLockingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function doRead($id)
    {
        try {
            // Execute a GetItem command to retrieve the item
            $result = $this->client->getCommand('GetItem', array(
                'TableName' => $this->config->get('table_name'),
                'Key' => $this->formatKey($id),
                'ConsistentRead' => (bool) $this->config->get('consistent_read'),
                Ua::OPTION       => Ua::SESSION
            ))->execute();

            // Get the item values
            $item   = array();
            $result = isset($result['Item']) ? $result['Item'] : array();
            foreach ($result as $key => $value) {
                $item[$key] = current($value);
            }
        } catch (DynamoDbException $e) {
            $item = array();
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtraAttributes()
    {
        // @codeCoverageIgnoreStart
        return array();
        // @codeCoverageIgnoreEnd
    }
}
