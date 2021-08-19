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
 * @package   MicrosoftAzure\Storage\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common;

use MicrosoftAzure\Storage\Common\Internal\Resources;

/**
 * Logger class for debugging purpose.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class Logger
{
    /**
     * @var string
     */
    private static $_filePath;

    /**
     * Logs $var to file.
     *
     * @param mixed  $var The data to log.
     * @param string $tip The help message.
     *
     * @return void
     */
    public static function log($var, $tip = Resources::EMPTY_STRING)
    {
        if (!empty($tip)) {
            error_log($tip . "\n", 3, self::$_filePath);
        }

        if (is_array($var) || is_object($var)) {
            error_log(print_r($var, true), 3, self::$_filePath);
        } else {
            error_log($var . "\n", 3, self::$_filePath);
        }
    }

    /**
     * Sets file path to use.
     *
     * @param string $filePath The log file path.
     * @return void
     */
    public static function setLogFile($filePath)
    {
        self::$_filePath = $filePath;
    }
}
