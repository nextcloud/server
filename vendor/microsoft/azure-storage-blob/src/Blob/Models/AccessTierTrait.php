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
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2018 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Blob\Models;

/**
 * Trait implementing setting and getting accessTier.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2018 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
trait AccessTierTrait
{
    /**
     * @var string $accessTier Version 2017-04-17 and newer. For page blobs on a premium storage account, otherwise a block blob
     *                         on blob storage account or storageV2 general account.
     *                         Specifies the tier to be set on the blob. Currently, for block blob, tiers like "Hot", "Cool"
     *                         and "Archive" can be used; for premium page blobs, "P4", "P6", "P10" and etc. can be set.
     *                         Check following link for a full list of supported tiers.
     *                         https://docs.microsoft.com/en-us/rest/api/storageservices/set-blob-tier
     */
    private $accessTier;

    /**
     * Gets blob access tier.
     *
     * @return string
     */
    public function getAccessTier()
    {
        return $this->accessTier;
    }

    /**
     * Sets blob access tier.
     *
     * @param string $accessTier value.
     *
     * @return void
     */
    public function setAccessTier($accessTier)
    {
        $this->accessTier = $accessTier;
    }
}
