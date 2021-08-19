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
use MicrosoftAzure\Storage\Blob\Internal\BlobResources as Resources;
use MicrosoftAzure\Storage\Common\Internal\Utilities;
use MicrosoftAzure\Storage\Common\Internal\Validate;

/**
 * Holds result of getContainerProperties and getContainerMetadata
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class GetContainerPropertiesResult
{
    use MetadataTrait;

    private $leaseStatus;
    private $leaseState;
    private $leaseDuration;
    private $publicAccess;

    /**
     * Gets blob leaseStatus.
     *
     * @return string
     */
    public function getLeaseStatus()
    {
        return $this->leaseStatus;
    }

    /**
     * Sets blob leaseStatus.
     *
     * @param string $leaseStatus value.
     *
     * @return void
     */
    public function setLeaseStatus($leaseStatus)
    {
        $this->leaseStatus = $leaseStatus;
    }

    /**
     * Gets blob lease state.
     *
     * @return string
     */
    public function getLeaseState()
    {
        return $this->leaseState;
    }

    /**
     * Sets blob lease state.
     *
     * @param string $leaseState value.
     *
     * @return void
     */
    public function setLeaseState($leaseState)
    {
        $this->leaseState = $leaseState;
    }

    /**
     * Gets blob lease duration.
     *
     * @return string
     */
    public function getLeaseDuration()
    {
        return $this->leaseDuration;
    }

    /**
     * Sets blob leaseStatus.
     *
     * @param string $leaseDuration value.
     *
     * @return void
     */
    public function setLeaseDuration($leaseDuration)
    {
        $this->leaseDuration = $leaseDuration;
    }

    /**
     * Gets container publicAccess.
     *
     * @return string
     */
    public function getPublicAccess()
    {
        return $this->publicAccess;
    }

    /**
     * Sets container publicAccess.
     *
     * @param string $publicAccess value.
     *
     * @return void
     */
    public function setPublicAccess($publicAccess)
    {
        Validate::isTrue(
            PublicAccessType::isValid($publicAccess),
            Resources::INVALID_BLOB_PAT_MSG
        );
        $this->publicAccess = $publicAccess;
    }

    /**
     * Create an instance using the response headers from the API call.
     *
     * @param  array  $responseHeaders The array contains all the response headers
     *
     * @internal
     *
     * @return GetContainerPropertiesResult
     */
    public static function create(array $responseHeaders)
    {
        $result   = static::createMetadataResult($responseHeaders);

        $result->setLeaseStatus(Utilities::tryGetValueInsensitive(
            Resources::X_MS_LEASE_STATUS,
            $responseHeaders
        ));
        $result->setLeaseState(Utilities::tryGetValueInsensitive(
            Resources::X_MS_LEASE_STATE,
            $responseHeaders
        ));
        $result->setLeaseDuration(Utilities::tryGetValueInsensitive(
            Resources::X_MS_LEASE_DURATION,
            $responseHeaders
        ));
        $result->setPublicAccess(Utilities::tryGetValueInsensitive(
            Resources::X_MS_BLOB_PUBLIC_ACCESS,
            $responseHeaders
        ));

        return $result;
    }
}
