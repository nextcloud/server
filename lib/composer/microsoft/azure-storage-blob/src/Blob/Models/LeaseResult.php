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
use MicrosoftAzure\Storage\Common\Internal\Utilities;

/**
 * The result of calling acquireLease API.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class LeaseResult
{
    private $leaseId;

    /**
     * Creates LeaseResult from response headers
     *
     * @param array $headers response headers
     *
     * @internal
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\LeaseResult
     */
    public static function create(array $headers)
    {
        $result = new LeaseResult();

        $result->setLeaseId(
            Utilities::tryGetValue($headers, Resources::X_MS_LEASE_ID)
        );

        return $result;
    }

    /**
     * Gets lease Id for the blob
     *
     * @return string
     */
    public function getLeaseId()
    {
        return $this->leaseId;
    }

    /**
     * Sets lease Id for the blob
     *
     * @param string $leaseId the blob lease id.
     *
     * @return void
     */
    protected function setLeaseId($leaseId)
    {
        $this->leaseId = $leaseId;
    }
}
