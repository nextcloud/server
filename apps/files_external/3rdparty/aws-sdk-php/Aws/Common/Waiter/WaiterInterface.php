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

namespace Aws\Common\Waiter;

/**
 * WaiterInterface used to wait on something to be in a particular state
 */
interface WaiterInterface
{
    const INTERVAL = 'waiter.interval';
    const MAX_ATTEMPTS = 'waiter.max_attempts';

    /**
     * Set the maximum number of attempts to make when waiting
     *
     * @param int $maxAttempts Max number of attempts
     *
     * @return self
     */
    public function setMaxAttempts($maxAttempts);

    /**
     * Set the amount of time to interval between attempts
     *
     * @param int $interval Interval in seconds
     *
     * @return self
     */
    public function setInterval($interval);

    /**
     * Set configuration options associated with the waiter
     *
     * @param array $config Configuration options to set
     *
     * @return self
     */
    public function setConfig(array $config);

    /**
     * Begin the waiting loop
     *
     * @throw RuntimeException if the method never resolves to true
     */
    public function wait();
}
