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
 * Optional parameters for setBlobProperties wrapper
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class SetBlobPropertiesOptions extends BlobServiceOptions
{
    private $_blobProperties;
    private $_sequenceNumberAction;

    /**
     * Creates a new SetBlobPropertiesOptions with a specified BlobProperties
     * instance.
     *
     * @param BlobProperties $blobProperties The blob properties instance.
     */
    public function __construct(BlobProperties $blobProperties = null)
    {
        parent::__construct();
        $this->_blobProperties = is_null($blobProperties)
                                 ? new BlobProperties() : clone $blobProperties;
    }

    /**
     * Gets blob sequenceNumber.
     *
     * @return integer
     */
    public function getSequenceNumber()
    {
        return $this->_blobProperties->getSequenceNumber();
    }

    /**
     * Sets blob sequenceNumber.
     *
     * @param integer $sequenceNumber value.
     *
     * @return void
     */
    public function setSequenceNumber($sequenceNumber)
    {
        $this->_blobProperties->setSequenceNumber($sequenceNumber);
    }

    /**
     * Gets lease Id for the blob
     *
     * @return string
     */
    public function getSequenceNumberAction()
    {
        return $this->_sequenceNumberAction;
    }

    /**
     * Sets lease Id for the blob
     *
     * @param string $sequenceNumberAction action.
     *
     * @return void
     */
    public function setSequenceNumberAction($sequenceNumberAction)
    {
        $this->_sequenceNumberAction = $sequenceNumberAction;
    }

    /**
     * Gets blob contentLength.
     *
     * @return integer
     */
    public function getContentLength()
    {
        return $this->_blobProperties->getContentLength();
    }

    /**
     * Sets blob contentLength.
     *
     * @param integer $contentLength value.
     *
     * @return void
     */
    public function setContentLength($contentLength)
    {
        $this->_blobProperties->setContentLength($contentLength);
    }

    /**
     * Gets ContentType.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->_blobProperties->getContentType();
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
        $this->_blobProperties->setContentType($contentType);
    }

    /**
     * Gets ContentEncoding.
     *
     * @return string
     */
    public function getContentEncoding()
    {
        return $this->_blobProperties->getContentEncoding();
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
        $this->_blobProperties->setContentEncoding($contentEncoding);
    }

    /**
     * Gets ContentLanguage.
     *
     * @return string
     */
    public function getContentLanguage()
    {
        return $this->_blobProperties->getContentLanguage();
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
        $this->_blobProperties->setContentLanguage($contentLanguage);
    }

    /**
     * Gets ContentMD5.
     *
     * @return void
     */
    public function getContentMD5()
    {
        return $this->_blobProperties->getContentMD5();
    }

    /**
     * Sets blob ContentMD5.
     *
     * @param string $contentMD5 value.
     *
     * @return void
     */
    public function setContentMD5($contentMD5)
    {
        $this->_blobProperties->setContentMD5($contentMD5);
    }

    /**
     * Gets cache control.
     *
     * @return string
     */
    public function getCacheControl()
    {
        return $this->_blobProperties->getCacheControl();
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
        $this->_blobProperties->setCacheControl($cacheControl);
    }

    /**
     * Gets content disposition.
     *
     * @return string
     */
    public function getContentDisposition()
    {
        return $this->_blobProperties->getContentDisposition();
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
        $this->_blobProperties->setContentDisposition($contentDisposition);
    }
}
