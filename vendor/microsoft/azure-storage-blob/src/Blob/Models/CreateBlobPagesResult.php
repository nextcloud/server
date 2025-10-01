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
use MicrosoftAzure\Storage\Common\Internal\Validate;
use MicrosoftAzure\Storage\Common\Internal\Utilities;

/**
 * Holds result of calling create or clear blob pages
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class CreateBlobPagesResult
{
    private $contentMD5;
    private $etag;
    private $lastModified;
    private $requestServerEncrypted;
    private $sequenceNumber;

    /**
     * Creates CreateBlobPagesResult object from $parsed response in array
     * representation
     *
     * @param array $headers HTTP response headers
     *
     * @internal
     *
     * @return CreateBlobPagesResult
     */
    public static function create(array $headers)
    {
        $result = new CreateBlobPagesResult();
        $clean  = array_change_key_case($headers);

        $date = $clean[Resources::LAST_MODIFIED];
        $date = Utilities::rfc1123ToDateTime($date);
        $result->setETag($clean[Resources::ETAG]);
        $result->setLastModified($date);

        $result->setContentMD5(
            Utilities::tryGetValue($clean, Resources::CONTENT_MD5)
        );

        $result->setRequestServerEncrypted(
            Utilities::toBoolean(
                Utilities::tryGetValueInsensitive(
                    Resources::X_MS_REQUEST_SERVER_ENCRYPTED,
                    $headers
                ),
                true
            )
        );

        $result->setSequenceNumber(
            intval(
                Utilities::tryGetValue(
                    $clean,
                    Resources::X_MS_BLOB_SEQUENCE_NUMBER
                )
            )
        );

        return $result;
    }

    /**
     * Gets blob lastModified.
     *
     * @return \DateTime.
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Sets blob lastModified.
     *
     * @param \DateTime $lastModified value.
     *
     * @return void
     */
    protected function setLastModified($lastModified)
    {
        Validate::isDate($lastModified);
        $this->lastModified = $lastModified;
    }

    /**
     * Gets blob etag.
     *
     * @return string
     */
    public function getETag()
    {
        return $this->etag;
    }

    /**
     * Sets blob etag.
     *
     * @param string $etag value.
     *
     * @return void
     */
    protected function setETag($etag)
    {
        Validate::canCastAsString($etag, 'etag');
        $this->etag = $etag;
    }

    /**
     * Gets blob contentMD5.
     *
     * @return string
     */
    public function getContentMD5()
    {
        return $this->contentMD5;
    }

    /**
     * Sets blob contentMD5.
     *
     * @param string $contentMD5 value.
     *
     * @return void
     */
    protected function setContentMD5($contentMD5)
    {
        $this->contentMD5 = $contentMD5;
    }

    /**
     * Gets the whether the contents of the request are successfully encrypted.
     *
     * @return boolean
     */
    public function getRequestServerEncrypted()
    {
        return $this->requestServerEncrypted;
    }

    /**
     * Sets the request server encryption value.
     *
     * @param boolean $requestServerEncrypted
     *
     * @return void
     */
    public function setRequestServerEncrypted($requestServerEncrypted)
    {
        $this->requestServerEncrypted = $requestServerEncrypted;
    }

    /**
     * Gets blob sequenceNumber.
     *
     * @return int
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * Sets blob sequenceNumber.
     *
     * @param int $sequenceNumber value.
     *
     * @return void
     */
    protected function setSequenceNumber($sequenceNumber)
    {
        Validate::isInteger($sequenceNumber, 'sequenceNumber');
        $this->sequenceNumber = $sequenceNumber;
    }
}
