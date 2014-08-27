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

use Aws\Common\Exception\RuntimeException;
use Guzzle\Common\AbstractHasDispatcher;

/**
 * Abstract wait implementation
 */
abstract class AbstractWaiter extends AbstractHasDispatcher implements WaiterInterface
{
    protected $attempts = 0;
    protected $config = array();

    /**
     * {@inheritdoc}
     */
    public static function getAllEvents()
    {
        return array(
            // About to check if the waiter needs to wait
            'waiter.before_attempt',
            // About to sleep
            'waiter.before_wait',
        );
    }

    /**
     * The max attempts allowed by the waiter
     *
     * @return int
     */
    public function getMaxAttempts()
    {
        return isset($this->config[self::MAX_ATTEMPTS]) ? $this->config[self::MAX_ATTEMPTS] : 10;
    }

    /**
     * Get the amount of time in seconds to delay between attempts
     *
     * @return int
     */
    public function getInterval()
    {
        return isset($this->config[self::INTERVAL]) ? $this->config[self::INTERVAL] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setMaxAttempts($maxAttempts)
    {
        $this->config[self::MAX_ATTEMPTS] = $maxAttempts;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setInterval($interval)
    {
        $this->config[self::INTERVAL] = $interval;

        return $this;
    }

    /**
     * Set config options associated with the waiter
     *
     * @param array $config Options to set
     *
     * @return self
     */
    public function setConfig(array $config)
    {
        if (isset($config['waiter.before_attempt'])) {
            $this->getEventDispatcher()->addListener('waiter.before_attempt', $config['waiter.before_attempt']);
            unset($config['waiter.before_attempt']);
        }

        if (isset($config['waiter.before_wait'])) {
            $this->getEventDispatcher()->addListener('waiter.before_wait', $config['waiter.before_wait']);
            unset($config['waiter.before_wait']);
        }

        $this->config = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wait()
    {
        $this->attempts = 0;

        do {
            $this->dispatch('waiter.before_attempt', array(
                'waiter' => $this,
                'config' => $this->config,
            ));

            if ($this->doWait()) {
                break;
            }

            if (++$this->attempts >= $this->getMaxAttempts()) {
                throw new RuntimeException('Wait method never resolved to true after ' . $this->attempts . ' attempts');
            }

            $this->dispatch('waiter.before_wait', array(
                'waiter' => $this,
                'config' => $this->config,
            ));

            if ($this->getInterval()) {
                usleep($this->getInterval() * 1000000);
            }

        } while (1);
    }

    /**
     * Method to implement in subclasses
     *
     * @return bool Return true when successful, false on failure
     */
    abstract protected function doWait();
}
