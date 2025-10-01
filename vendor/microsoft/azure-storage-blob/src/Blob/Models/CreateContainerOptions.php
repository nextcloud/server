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

use MicrosoftAzure\Storage\Common\Internal\Validate;

/**
 * Optional parameters for createContainer API
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class CreateContainerOptions extends BlobServiceOptions
{
    private $_publicAccess;
    private $_metadata;

    /**
     * Gets container public access.
     *
     * @return string
     */
    public function getPublicAccess()
    {
        return $this->_publicAccess;
    }

    /**
     * Specifies whether data in the container may be accessed publicly and the level
     * of access. Possible values include:
     * 1) container: Specifies full public read access for container and blob data.
     *    Clients can enumerate blobs within the container via anonymous request, but
     *    cannot enumerate containers within the storage account.
     * 2) blob: Specifies public read access for blobs. Blob data within this
     *    container can be read via anonymous request, but container data is not
     *    available. Clients cannot enumerate blobs within the container via
     *    anonymous request.
     * If this value is not specified in the request, container data is private to
     * the account owner.
     *
     * @param string $publicAccess access modifier for the container
     *
     * @return void
     */
    public function setPublicAccess($publicAccess)
    {
        Validate::canCastAsString($publicAccess, 'publicAccess');
        $this->_publicAccess = $publicAccess;
    }

    /**
     * Gets user defined metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->_metadata;
    }

    /**
     * Sets user defined metadata. This metadata should be added without the header
     * prefix (x-ms-meta-*).
     *
     * @param array $metadata user defined metadata object in array form.
     *
     * @return void
     */
    public function setMetadata(array $metadata)
    {
        $this->_metadata = $metadata;
    }

    /**
     * Adds new metadata element. This element should be added without the header
     * prefix (x-ms-meta-*).
     *
     * @param string $key   metadata key element.
     * @param string $value metadata value element.
     *
     * @return void
     */
    public function addMetadata($key, $value)
    {
        $this->_metadata[$key] = $value;
    }
}
