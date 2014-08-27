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

namespace Aws\Common;

use Aws\Common\Facade\Facade;
use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Builder\ServiceBuilderLoader;

/**
 * Base class for interacting with web service clients
 */
class Aws extends ServiceBuilder
{
    /**
     * @var string Current version of the SDK
     */
    const VERSION = '2.6.15';

    /**
     * Create a new service locator for the AWS SDK
     *
     * You can configure the service locator is four different ways:
     *
     * 1. Use the default configuration file shipped with the SDK that wires class names with service short names and
     *    specify global parameters to add to every definition (e.g. key, secret, credentials, etc)
     *
     * 2. Use a custom configuration file that extends the default config and supplies credentials for each service.
     *
     * 3. Use a custom config file that wires services to custom short names for services.
     *
     * 4. If you are on Amazon EC2, you can use the default configuration file and not provide any credentials so that
     *    you are using InstanceProfile credentials.
     *
     * @param array|string $config           The full path to a .php or .js|.json file, or an associative array of data
     *                                       to use as global parameters to pass to each service.
     * @param array        $globalParameters Global parameters to pass to every service as it is instantiated.
     *
     * @return Aws
     */
    public static function factory($config = null, array $globalParameters = array())
    {
        if (!$config) {
            // If nothing is passed in, then use the default configuration file with credentials from the environment
            $config = self::getDefaultServiceDefinition();
        } elseif (is_array($config)) {
            // If an array was passed, then use the default configuration file with parameter overrides
            $globalParameters = $config;
            $config = self::getDefaultServiceDefinition();
        }

        $loader = new ServiceBuilderLoader();
        $loader->addAlias('_aws', self::getDefaultServiceDefinition())
            ->addAlias('_sdk1', __DIR__  . '/Resources/sdk1-config.php');

        return $loader->load($config, $globalParameters);
    }

    /**
     * Get the full path to the default service builder definition file
     *
     * @return string
     */
    public static function getDefaultServiceDefinition()
    {
        return __DIR__  . '/Resources/aws-config.php';
    }

    /**
     * Returns the configuration for the service builder
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->builderConfig;
    }

    /**
     * Enables the facades for the clients defined in the service builder
     *
     * @param string|null $namespace The namespace that the facades should be mounted to. Defaults to global namespace
     *
     * @return Aws
     */
    public function enableFacades($namespace = null)
    {
        Facade::mountFacades($this, $namespace);

        return $this;
    }
}
