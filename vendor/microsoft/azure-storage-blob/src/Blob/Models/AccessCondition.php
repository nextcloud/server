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
use MicrosoftAzure\Storage\Common\Internal\WindowsAzureUtilities;

/**
 * Represents a set of access conditions to be used for operations against the
 * storage services.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class AccessCondition
{
    private $_header = Resources::EMPTY_STRING;
    private $_value;

    /**
     * Constructor
     *
     * @param string $headerType header name
     * @param string $value      header value
     *
     * @internal
     */
    protected function __construct($headerType, $value)
    {
        $this->setHeader($headerType);
        $this->setValue($value);
    }

    /**
     * Specifies that no access condition is set.
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function none()
    {
        return new AccessCondition(Resources::EMPTY_STRING, null);
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the resource's ETag value matches the specified ETag value.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>If-Match</i> conditional header. If this access condition is set, the
     * operation is performed only if the ETag of the resource matches the specified
     * ETag.
     * <p>
     * For more information, see
     * <a href= 'http://go.microsoft.com/fwlink/?LinkID=224642&clcid=0x409'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param string $etag a string that represents the ETag value to check.
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function ifMatch($etag)
    {
        return new AccessCondition(Resources::IF_MATCH, $etag);
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the resource has been modified since the specified time.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>If-Modified-Since</i> conditional header. If this access condition is set,
     * the operation is performed only if the resource has been modified since the
     * specified time.
     * <p>
     * For more information, see
     * <a href= 'http://go.microsoft.com/fwlink/?LinkID=224642&clcid=0x409'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param \DateTime $lastModified date that represents the last-modified
     * time to check for the resource.
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function ifModifiedSince(\DateTime $lastModified)
    {
        Validate::isDate($lastModified);
        return new AccessCondition(
            Resources::IF_MODIFIED_SINCE,
            $lastModified
        );
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the resource's ETag value does not match the specified ETag value.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>If-None-Match</i> conditional header. If this access condition is set, the
     * operation is performed only if the ETag of the resource does not match the
     * specified ETag.
     * <p>
     * For more information,
     * see <a href= 'http://go.microsoft.com/fwlink/?LinkID=224642&clcid=0x409'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param string $etag string that represents the ETag value to check.
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function ifNoneMatch($etag)
    {
        return new AccessCondition(Resources::IF_NONE_MATCH, $etag);
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the resource has not been modified since the specified time.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>If-Unmodified-Since</i> conditional header. If this access condition is
     * set, the operation is performed only if the resource has not been modified
     * since the specified time.
     * <p>
     * For more information, see
     * <a href= 'http://go.microsoft.com/fwlink/?LinkID=224642&clcid=0x409'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param \DateTime $lastModified date that represents the last-modified
     * time to check for the resource.
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function ifNotModifiedSince(\DateTime $lastModified)
    {
        Validate::isDate($lastModified);
        return new AccessCondition(
            Resources::IF_UNMODIFIED_SINCE,
            $lastModified
        );
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the operation would cause the blob to exceed that limit or if the append
     * position is equal to this number.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>x-ms-blob-condition-appendpos</i> conditional header. If this access condition
     * is set, the operation is performed only if the append position is equal to this number
     * <p>
     * For more information,
     * see <a href= 'https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/append-block'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param int $appendPosition int that represents the append position
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function appendPosition($appendPosition)
    {
        return new AccessCondition(Resources::MAX_APPEND_POSITION, $appendPosition);
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the operation would cause the blob to exceed that limit or if the blob size
     * is already greater than the value specified in this header.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>x-ms-blob-condition-maxsize</i> conditional header. If this access condition
     * is set, the operation is performed only if the operation would cause the blob
     * to exceed that limit or if the blob size is already greater than the value
     * specified in this header.
     * <p>
     * For more information,
     * see <a href= 'https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/append-block'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param int $maxBlobSize int that represents the max blob size
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function maxBlobSize($maxBlobSize)
    {
        return new AccessCondition(Resources::MAX_BLOB_SIZE, $maxBlobSize);
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the blob’s sequence number is less than the specified value.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>x-ms-if-sequence-number-lt</i> conditional header. If this access condition
     * is set, the operation is performed only if the blob’s sequence number is less
     * than the specified value.
     * <p>
     * For more information,
     * see <a href= 'https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/put-page'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param int $sequenceNumber int that represents the sequence number value to check.
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function ifSequenceNumberLessThan($sequenceNumber)
    {
        return new AccessCondition(Resources::SEQUENCE_NUMBER_LESS_THAN, $sequenceNumber);
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the blob’s sequence number is equal to the specified value.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>x-ms-if-sequence-number-eq</i> conditional header. If this access condition
     * is set, the operation is performed only if the blob’s sequence number is equal to
     * the specified value.
     * <p>
     * For more information,
     * see <a href= 'https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/put-page'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param int $sequenceNumber int that represents the sequence number value to check.
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function ifSequenceNumberEqual($sequenceNumber)
    {
        return new AccessCondition(Resources::SEQUENCE_NUMBER_EQUAL, $sequenceNumber);
    }

    /**
     * Returns an access condition such that an operation will be performed only if
     * the blob’s sequence number is less than or equal to the specified value.
     * <p>
     * Setting this access condition modifies the request to include the HTTP
     * <i>x-ms-if-sequence-number-le</i> conditional header. If this access condition
     * is set, the operation is performed only if the blob’s sequence number is less
     * than or equal to the specified value.
     * <p>
     * For more information,
     * see <a href= 'https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/put-page'>
     * Specifying Conditional Headers for Blob Service Operations</a>.
     *
     * @param int $sequenceNumber int that represents the sequence number value to check.
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\AccessCondition
     */
    public static function ifSequenceNumberLessThanOrEqual($sequenceNumber)
    {
        return new AccessCondition(Resources::SEQUENCE_NUMBER_LESS_THAN_OR_EQUAL, $sequenceNumber);
    }

    /**
     * Sets header type
     *
     * @param string $headerType can be one of Resources
     *
     * @return void
     */
    public function setHeader($headerType)
    {
        $valid = AccessCondition::isValid($headerType);
        Validate::isTrue($valid, Resources::INVALID_HT_MSG);

        $this->_header = $headerType;
    }

    /**
     * Gets header type
     *
     * @return string
     */
    public function getHeader()
    {
        return $this->_header;
    }

    /**
     * Sets the header value
     *
     * @param string $value the value to use
     *
     * @return void
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * Gets the header value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Check if the $headerType belongs to valid header types
     *
     * @param string $headerType candidate header type
     *
     * @internal
     *
     * @return boolean
     */
    public static function isValid($headerType)
    {
        if ($headerType == Resources::EMPTY_STRING
            || $headerType == Resources::IF_UNMODIFIED_SINCE
            || $headerType == Resources::IF_MATCH
            || $headerType == Resources::IF_MODIFIED_SINCE
            || $headerType == Resources::IF_NONE_MATCH
            || $headerType == Resources::MAX_BLOB_SIZE
            || $headerType == Resources::MAX_APPEND_POSITION
            || $headerType == Resources::SEQUENCE_NUMBER_LESS_THAN_OR_EQUAL
            || $headerType == Resources::SEQUENCE_NUMBER_LESS_THAN
            || $headerType == Resources::SEQUENCE_NUMBER_EQUAL
        ) {
            return true;
        } else {
            return false;
        }
    }
}
