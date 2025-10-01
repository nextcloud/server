<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal;

/**
 * Holder for default connection string sources used in CloudConfigurationManager.
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class ConnectionStringSource
{
    private static $_defaultSources;
    private static $_isInitialized;
    const ENVIRONMENT_SOURCE = 'environment_source';

    /**
     * Initializes the default sources.
     *
     * @return void
     */
    private static function _init()
    {
        if (!self::$_isInitialized) {
            self::$_defaultSources = array(
                self::ENVIRONMENT_SOURCE => array(__CLASS__, 'environmentSource')
            );
            self::$_isInitialized  = true;
        }
    }

    /**
     * Gets a connection string value from the system environment.
     *
     * @param string $key The connection string name.
     *
     * @return string
     */
    public static function environmentSource($key)
    {
        Validate::canCastAsString($key, 'key');

        return getenv($key);
    }

    /**
     * Gets list of default sources.
     *
     * @return array
     */
    public static function getDefaultSources()
    {
        self::_init();
        return self::$_defaultSources;
    }
}
