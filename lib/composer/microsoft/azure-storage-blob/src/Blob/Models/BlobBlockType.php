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
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Blob\Models;

/**
 * Holds available blob block types
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class BlobBlockType
{
    const COMMITTED_TYPE   = 'Committed';
    const UNCOMMITTED_TYPE = 'Uncommitted';
    const LATEST_TYPE      = 'Latest';

    /**
     * Validates the provided type.
     *
     * @param string $type The entry type.
     *
     * @internal
     *
     * @return boolean
     */
    public static function isValid($type)
    {
        switch ($type) {
            case self::COMMITTED_TYPE:
            case self::LATEST_TYPE:
            case self::UNCOMMITTED_TYPE:
                return true;

            default:
                return false;
        }
    }
}
