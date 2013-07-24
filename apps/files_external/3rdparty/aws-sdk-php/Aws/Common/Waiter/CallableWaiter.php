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

use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\RuntimeException;

/**
 * Callable wait implementation
 */
class CallableWaiter extends AbstractWaiter
{
    /**
     * @var callable Callable function
     */
    protected $callable;

    /**
     * @var array Additional context for the callable function
     */
    protected $context = array();

    /**
     * Set the callable function to call in each wait attempt
     *
     * @param callable $callable Callable function
     *
     * @return self
     * @throws InvalidArgumentException when the method is not callable
     */
    public function setCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Value is not callable');
        }

        $this->callable = $callable;

        return $this;
    }

    /**
     * Set additional context for the callable function. This data will be passed into the callable function as the
     * second argument
     *
     * @param array $context Additional context
     *
     * @return self
     */
    public function setContext(array $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function doWait()
    {
        if (!$this->callable) {
            throw new RuntimeException('No callable was specified for the wait method');
        }

        return call_user_func($this->callable, $this->attempts, $this->context);
    }
}
