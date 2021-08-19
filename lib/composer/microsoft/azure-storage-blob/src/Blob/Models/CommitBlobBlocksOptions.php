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
 * Optional parameters for commitBlobBlocks
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class CommitBlobBlocksOptions extends BlobServiceOptions
{
    private $_contentType;
    private $_contentEncoding;
    private $_contentLanguage;
    private $_contentMD5;
    private $_cacheControl;
    private $_contentDisposition;
    private $_metadata;

    /**
     * Gets ContentType.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->_contentType;
    }

    /**
     * Sets ContentType.
     *
     * @param string $contentType value.
     *
     * @return void
     */
    public function setContentType($contentType)
    {
        $this->_contentType = $contentType;
    }

    /**
     * Gets ContentEncoding.
     *
     * @return string
     */
    public function getContentEncoding()
    {
        return $this->_contentEncoding;
    }

    /**
     * Sets ContentEncoding.
     *
     * @param string $contentEncoding value.
     *
     * @return void
     */
    public function setContentEncoding($contentEncoding)
    {
        $this->_contentEncoding = $contentEncoding;
    }

    /**
     * Gets ContentLanguage.
     *
     * @return string
     */
    public function getContentLanguage()
    {
        return $this->_contentLanguage;
    }

    /**
     * Sets ContentLanguage.
     *
     * @param string $contentLanguage value.
     *
     * @return void
     */
    public function setContentLanguage($contentLanguage)
    {
        $this->_contentLanguage = $contentLanguage;
    }

    /**
     * Gets ContentMD5.
     *
     * @return string
     */
    public function getContentMD5()
    {
        return $this->_contentMD5;
    }

    /**
     * Sets ContentMD5.
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
     * Gets cache control.
     *
     * @return string
     */
    public function getCacheControl()
    {
        return $this->_cacheControl;
    }

    /**
     * Sets cacheControl.
     *
     * @param string $cacheControl value to use.
     *
     * @return void
     */
    public function setCacheControl($cacheControl)
    {
        $this->_cacheControl = $cacheControl;
    }

    /**
     * Gets content disposition.
     *
     * @return string
     */
    public function getContentDisposition()
    {
        return $this->_contentDisposition;
    }

    /**
     * Sets contentDisposition.
     *
     * @param string $contentDisposition value to use.
     *
     * @return void
     */
    public function setContentDisposition($contentDisposition)
    {
        $this->_contentDisposition = $contentDisposition;
    }

    /**
     * Gets blob metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->_metadata;
    }

    /**
     * Sets blob metadata.
     *
     * @param array $metadata value.
     *
     * @return void
     */
    public function setMetadata(array $metadata = null)
    {
        $this->_metadata = $metadata;
    }

    /**
     * Create a instance using the given options
     * @param  mixed $options Input options
     *
     * @internal
     *
     * @return self
     */
    public static function create($options)
    {
        $result = new CommitBlobBlocksOptions();
        $result->setContentType($options->getContentType());
        $result->setContentEncoding($options->getContentEncoding());
        $result->setContentLanguage($options->getContentLanguage());
        $result->setContentMD5($options->getContentMD5());
        $result->setCacheControl($options->getCacheControl());
        $result->setContentDisposition($options->getContentDisposition());
        $result->setMetadata($options->getMetadata());
        $result->setLeaseId($options->getLeaseId());
        $result->setAccessConditions($options->getAccessConditions());

        return $result;
    }
}
