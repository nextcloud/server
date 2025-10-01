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
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Models;

use MicrosoftAzure\Storage\Common\Internal\Validate;

/**
 * Provides functionality and data structure for continuation token that
 * contains next marker.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class MarkerContinuationToken extends ContinuationToken
{
    private $nextMarker;

    public function __construct(
        $nextMarker = '',
        $location = ''
    ) {
        parent::__construct($location);
        $this->setNextMarker($nextMarker);
    }

    /**
     * Setter for nextMarker
     *
     * @param string $nextMarker the next marker to be set.
     */
    public function setNextMarker($nextMarker)
    {
        Validate::canCastAsString($nextMarker, 'nextMarker');
        $this->nextMarker = $nextMarker;
    }

    /**
     * Getter for nextMarker
     *
     * @return string
     */
    public function getNextMarker()
    {
        return $this->nextMarker;
    }
}
