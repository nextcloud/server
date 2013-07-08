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
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Inflection\Inflector;
use Guzzle\Inflection\InflectorInterface;

/**
 * Factory for instantiating locking strategies
 */
class LockingStrategyFactory
{
    /**
     * @var string Base namespace used to look for classes
     */
    protected $baseNamespace;

    /**
     * @var InflectorInterface Inflector used to inflect class names
     */
    protected $inflector;

    /**
     * @param string             $baseNamespace Base namespace of all locking strategies
     * @param InflectorInterface $inflector     Inflector used to resolve class names
     */
    public function __construct($baseNamespace = null, InflectorInterface $inflector = null)
    {
        $this->baseNamespace = $baseNamespace ?: __NAMESPACE__;
        $this->inflector = $inflector ?: Inflector::getDefault();
    }

    /**
     * Creates a session handler locking strategy
     *
     * @param string               $lockingStrategy The name if the locking strategy
     * @param SessionHandlerConfig $config          The session handler config data
     *
     * @return LockingStrategyInterface
     *
     * @throws InvalidArgumentException If the locking strategy doesn't exist
     */
    public function factory($lockingStrategy = null, SessionHandlerConfig $config = null)
    {
        // If the locking strategy is null, let's give it the name "null"
        if ($lockingStrategy === null) {
            $lockingStrategy = 'null';
        }

        // Make sure the locking strategy name provided is a string
        if (!is_string($lockingStrategy)) {
            throw new InvalidArgumentException('The session locking strategy '
                . 'name must be provided as a string.');
        }

        // Determine the class name of the locking strategy class
        $classPath = $this->baseNamespace . '\\'
            . $this->inflector->camel($lockingStrategy) . 'LockingStrategy';

        // Make sure the locking strategy class exists
        if (!class_exists($classPath)) {
            throw new InvalidArgumentException("There is no session locking "
                . "strategy named \"{$classPath}\".");
        }

        // Call the factory on the locking strategy class to create it
        return new $classPath($config->get('dynamodb_client'), $config);
    }
}
