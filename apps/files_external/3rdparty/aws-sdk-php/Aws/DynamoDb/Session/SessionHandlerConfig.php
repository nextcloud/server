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

namespace Aws\DynamoDb\Session;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Common\Exception\InvalidArgumentException;

/**
 * A simple object containing config values for the session handler
 */
class SessionHandlerConfig
{
    /**
     * @var array The configuration data
     */
    protected $data;

    /**
     * Constructs the session handler config with default values
     *
     * @param array $data The config data
     *
     * @throws InvalidArgumentException If a valid client isn't provided
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;

        // Make sure the DynamoDB client has been provided
        if (!($this->get('dynamodb_client') instanceof DynamoDbClient)) {
            throw new InvalidArgumentException('The DynamoDB Session Handler '
                . 'must be provided an instance of the DynamoDbClient.');
        }

        // Merge provided data with defaults
        $this->addDefaults(array(
            'table_name'         => 'sessions',
            'hash_key'           => 'id',
            'session_lifetime'   => (int) ini_get('session.gc_maxlifetime'),
            'consistent_read'    => true,
            'automatic_gc'       => (bool) ini_get('session.gc_probability'),
            'gc_batch_size'      => 25,
            'gc_operation_delay' => 0,
        ));
    }

    /**
     * Gets a config value if it exists, otherwise it returns null
     *
     * @param string $key The key of the config item
     *
     * @return mixed
     */
    public function get($key)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }

    /**
     * Applies default values by merging underneath the current data
     *
     * @param array $defaults The new default data to merge underneath
     *
     * @return SessionHandlerConfig
     */
    public function addDefaults(array $defaults)
    {
        $this->data = array_replace($defaults, $this->data);

        return $this;
    }
}
