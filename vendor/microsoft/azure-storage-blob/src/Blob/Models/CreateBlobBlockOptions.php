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

/**
 * Optional parameters for createBlobBlock wrapper
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class CreateBlobBlockOptions extends BlobServiceOptions
{
    private $_contentMD5;
    private $_numberOfConcurrency;

    /**
     * Gets blob contentMD5.
     *
     * @return string
     */
    public function getContentMD5()
    {
        return $this->_contentMD5;
    }

    /**
     * Sets blob contentMD5.
     *
     * @param string $contentMD5 value.
     *
     * @return void
     */
    public function setContentMD5($contentMD5)
    {
        $this->_contentMD5 = $contentMD5;
    }

    /**
     * Gets number of concurrency for sending a blob.
     *
     * @return int
     */
    public function getNumberOfConcurrency()
    {
        return $this->_numberOfConcurrency;
    }

    /**
     * Sets number of concurrency for sending a blob.
     *
     * @param int $numberOfConcurrency the number of concurrent requests.
     */
    public function setNumberOfConcurrency($numberOfConcurrency)
    {
        $this->_numberOfConcurrency = $numberOfConcurrency;
    }

    /**
     * Construct a CreateBlobBlockOptions object from a createBlobOptions.
     *
     * @param  CreateBlobOptions $createBlobOptions
     *
     * @return CreateBlobBlockOptions
     */
    public static function create(CreateBlobOptions $createBlobOptions)
    {
        $result = new CreateBlobBlockOptions();
        $result->setTimeout($createBlobOptions->getTimeout());
        $result->setLeaseId($createBlobOptions->getLeaseId());
        $result->setNumberOfConcurrency(
            $createBlobOptions->getNumberOfConcurrency()
        );
        return $result;
    }
}
