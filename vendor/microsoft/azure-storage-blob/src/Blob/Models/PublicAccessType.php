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

use MicrosoftAzure\Storage\Blob\Internal\BlobResources as Resources;

/**
 * Holds public access types for a container.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class PublicAccessType
{
    const NONE                = null;
    const BLOBS_ONLY          = 'blob';
    const CONTAINER_AND_BLOBS = 'container';

    /**
     * Validates the public access.
     *
     * @param string $type The public access type.
     *
     * @internal
     *
     * @return boolean
     */
    public static function isValid($type)
    {
        // When $type is null, switch statement will take it
        // equal to self::NONE (EMPTY_STRING)
        switch ($type) {
            case self::NONE:
            case self::BLOBS_ONLY:
            case self::CONTAINER_AND_BLOBS:
                return true;
            default:
                return false;
        }
    }
}
