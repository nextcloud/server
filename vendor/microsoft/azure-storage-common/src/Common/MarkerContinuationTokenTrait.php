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
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common;

use MicrosoftAzure\Storage\Common\Models\MarkerContinuationToken;

/**
 * Trait implementing logic for continuation tokens that has nextMarker.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
trait MarkerContinuationTokenTrait
{
    private $continuationToken;

    /**
     * Setter for continuationToken
     *
     * @param MarkerContinuationToken|null $continuationToken the continuation
     *                                                        token to be set.
     */
    public function setContinuationToken(MarkerContinuationToken $continuationToken = null)
    {
        $this->continuationToken = $continuationToken;
    }

    public function setMarker($marker)
    {
        if ($this->continuationToken == null) {
            $this->continuationToken = new MarkerContinuationToken();
        };
        $this->continuationToken->setNextMarker($marker);
    }

    /**
     * Getter for continuationToken
     *
     * @return MarkerContinuationToken
     */
    public function getContinuationToken()
    {
        return $this->continuationToken;
    }

    /**
     * Gets the next marker to list/query items.
     *
     * @return string
     */
    public function getNextMarker()
    {
        if ($this->continuationToken == null) {
            return null;
        }
        return $this->continuationToken->getNextMarker();
    }

    /**
     * Gets for location for previous request.
     *
     * @return string
     */
    public function getLocation()
    {
        if ($this->continuationToken == null) {
            return null;
        }
        return $this->continuationToken->getLocation();
    }

    public function getLocationMode()
    {
        if ($this->continuationToken == null) {
            return parent::getLocationMode();
        } elseif ($this->continuationToken->getLocation() == '') {
            return parent::getLocationMode();
        } else {
            return $this->getLocation();
        }
    }
}
