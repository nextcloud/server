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

/**
 * Factory that utilizes multiple factories for creating waiters
 */
class CompositeWaiterFactory implements WaiterFactoryInterface
{
    /**
     * @var array Array of factories
     */
    protected $factories;

    /**
     * @param array $factories Array of factories used to instantiate waiters
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     * {@inheritdoc}
     */
    public function build($waiter)
    {
        if (!($factory = $this->getFactory($waiter))) {
            throw new InvalidArgumentException("Waiter was not found matching {$waiter}.");
        }

        return $factory->build($waiter);
    }

    /**
     * {@inheritdoc}
     */
    public function canBuild($waiter)
    {
        return (bool) $this->getFactory($waiter);
    }

    /**
     * Add a factory to the composite factory
     *
     * @param WaiterFactoryInterface $factory Factory to add
     *
     * @return self
     */
    public function addFactory(WaiterFactoryInterface $factory)
    {
        $this->factories[] = $factory;

        return $this;
    }

    /**
     * Get the factory that matches the waiter name
     *
     * @param string $waiter Name of the waiter
     *
     * @return WaiterFactoryInterface|bool
     */
    protected function getFactory($waiter)
    {
        foreach ($this->factories as $factory) {
            if ($factory->canBuild($waiter)) {
                return $factory;
            }
        }

        return false;
    }
}
