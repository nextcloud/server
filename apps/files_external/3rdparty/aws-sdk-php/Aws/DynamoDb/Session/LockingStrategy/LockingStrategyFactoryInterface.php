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

use Aws\DynamoDb\Session\SessionHandlerConfig;

/**
 * Interface for locking strategy factories. Useful for those who are creating
 * their own locking strategies.
 */
interface LockingStrategyFactoryInterface
{
    /**
     * Creates a session handler locking strategy object
     *
     * @param string               $lockingStrategy The name of the locking strategy
     * @param SessionHandlerConfig $config          The session handler config data
     *
     * @return LockingStrategyInterface
     */
    public function factory($lockingStrategy, SessionHandlerConfig $config);
}
