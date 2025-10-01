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

use MicrosoftAzure\Storage\Common\Internal\MetadataTrait;

/**
 * Holds result of calling getBlobProperties
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class GetBlobPropertiesResult
{
    use MetadataTrait;

    private $_properties;

    /**
     * Gets blob properties.
     *
     * @return BlobProperties
     */
    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * Sets blob properties.
     *
     * @param BlobProperties $properties value.
     *
     * @return void
     */
    protected function setProperties($properties)
    {
        $this->_properties = $properties;
    }

    /**
     * Create a instance using the given headers.
     *
     * @param  array  $headers response headers parsed in an array
     *
     * @internal
     *
     * @return GetBlobPropertiesResult
     */
    public static function create(array $headers)
    {
        $result = static::createMetadataResult($headers);

        $result->setProperties(BlobProperties::createFromHttpHeaders($headers));

        return $result;
    }
}
