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
use Guzzle\Inflection\Inflector;
use Guzzle\Inflection\InflectorInterface;

/**
 * Factory for creating {@see WaiterInterface} objects using a configuration DSL.
 */
class WaiterConfigFactory implements WaiterFactoryInterface
{
    /**
     * @var array Configuration directives
     */
    protected $config;

    /**
     * @var InflectorInterface Inflector used to inflect class names
     */
    protected $inflector;

    /**
     * @param array              $config    Array of configuration directives
     * @param InflectorInterface $inflector Inflector used to resolve class names
     */
    public function __construct(
        array $config,
        InflectorInterface $inflector = null
    ) {
        $this->config = $config;
        $this->inflector = $inflector ?: Inflector::getDefault();
    }

    /**
     * {@inheritdoc}
     */
    public function build($waiter)
    {
        return new ConfigResourceWaiter($this->getWaiterConfig($waiter));
    }

    /**
     * {@inheritdoc}
     */
    public function canBuild($waiter)
    {
        return isset($this->config[$waiter]) || isset($this->config[$this->inflector->camel($waiter)]);
    }

    /**
     * Get waiter configuration data, taking __default__ and extensions into account
     *
     * @param string $name Waiter name
     *
     * @return WaiterConfig
     * @throws InvalidArgumentException
     */
    protected function getWaiterConfig($name)
    {
        if (!$this->canBuild($name)) {
            throw new InvalidArgumentException('No waiter found matching "' . $name . '"');
        }

        // inflect the name if needed
        $name = isset($this->config[$name]) ? $name : $this->inflector->camel($name);
        $waiter = new WaiterConfig($this->config[$name]);
        $waiter['name'] = $name;

        // Always use __default__ as the basis if it's set
        if (isset($this->config['__default__'])) {
            $parentWaiter = new WaiterConfig($this->config['__default__']);
            $waiter = $parentWaiter->overwriteWith($waiter);
        }

        // Allow for configuration extensions
        if (isset($this->config[$name]['extends'])) {
            $waiter = $this->getWaiterConfig($this->config[$name]['extends'])->overwriteWith($waiter);
        }

        return $waiter;
    }
}
