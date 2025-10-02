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
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Blob\Models;

/**
 * Optional parameters for appendBlock wrapper
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class AppendBlockOptions extends BlobServiceOptions
{
    private $contentMD5;
    private $maxBlobSize;
    private $appendPosition;

    /**
     * Gets block contentMD5.
     *
     * @return string
     */
    public function getContentMD5()
    {
        return $this->contentMD5;
    }

    /**
     * Sets block contentMD5.
     *
     * @param string $contentMD5 value.
     *
     * @return void
     */
    public function setContentMD5($contentMD5)
    {
        $this->contentMD5 = $contentMD5;
    }

    /**
     * Gets the max length in bytes allowed for the append blob to grow to.
     *
     * @return int
     */
    public function getMaxBlobSize()
    {
        return $this->maxBlobSize;
    }

    /**
     * Sets the max length in bytes allowed for the append blob to grow to.
     *
     * @param int $maxBlobSize value.
     *
     * @return void
     */
    public function setMaxBlobSize($maxBlobSize)
    {
        $this->maxBlobSize = $maxBlobSize;
    }

    /**
     * Gets append blob appendPosition.
     *
     * @return int
     */
    public function getAppendPosition()
    {
        return $this->appendPosition;
    }

    /**
     * Sets append blob appendPosition.
     *
     * @param int $appendPosition value.
     *
     * @return void
     */
    public function setAppendPosition($appendPosition)
    {
        $this->appendPosition = $appendPosition;
    }
}
