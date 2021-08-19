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
use MicrosoftAzure\Storage\Common\Models\Range;

/**
 * Optional parameters for getBlob wrapper
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class GetBlobOptions extends BlobServiceOptions
{
    private $snapshot;
    private $range;
    private $rangeGetContentMD5;

    /**
     * Gets blob snapshot.
     *
     * @return string
     */
    public function getSnapshot()
    {
        return $this->snapshot;
    }

    /**
     * Sets blob snapshot.
     *
     * @param string $snapshot value.
     *
     * @return void
     */
    public function setSnapshot($snapshot)
    {
        $this->snapshot = $snapshot;
    }

    /**
     * Gets Blob range.
     *
     * @return Range
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * Sets Blob range.
     *
     * @param Range $range value.
     *
     * @return void
     */
    public function setRange(Range $range)
    {
        $this->range = $range;
    }

    /**
     * Gets rangeGetContentMD5
     *
     * @return boolean
     */
    public function getRangeGetContentMD5()
    {
        return $this->rangeGetContentMD5;
    }

    /**
     * Sets rangeGetContentMD5
     *
     * @param boolean $rangeGetContentMD5 value
     *
     * @return void
     */
    public function setRangeGetContentMD5($rangeGetContentMD5)
    {
        Validate::isBoolean($rangeGetContentMD5);
        $this->rangeGetContentMD5 = $rangeGetContentMD5;
    }
}
