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
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal;

/**
 * Trait implementing common logic for metadata, last-modified and etag. The
 * code is shared for multiple REST APIs.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
trait MetadataTrait
{
    private $lastModified;
    private $etag;
    private $metadata;

    /**
     * Any operation that modifies the share or its properties or metadata
     * updates the last modified time. Operations on files do not affect the
     * last modified time of the share.
     *
     * @return \DateTime.
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Sets share lastModified.
     *
     * @param \DateTime $lastModified value.
     *
     * @return void
     */
    protected function setLastModified(\DateTime $lastModified)
    {
        $this->lastModified = $lastModified;
    }

    /**
     * The entity tag for the share. If the request version is 2011-08-18 or
     * newer, the ETag value will be in quotes.
     *
     * @return string
     */
    public function getETag()
    {
        return $this->etag;
    }

    /**
     * Sets share etag.
     *
     * @param string $etag value.
     *
     * @return void
     */
    protected function setETag($etag)
    {
        $this->etag = $etag;
    }

    /**
     * Gets user defined metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Sets user defined metadata. This metadata should be added without the
     * header prefix (x-ms-meta-*).
     *
     * @param array $metadata user defined metadata object in array form.
     *
     * @return void
     */
    protected function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Create an instance using the response headers from the API call.
     *
     * @param  array  $responseHeaders The array contains all the response headers
     *
     * @internal
     *
     * @return GetShareMetadataResult
     */
    public static function createMetadataResult(array $responseHeaders)
    {
        $result   = new static();
        $metadata = Utilities::getMetadataArray($responseHeaders);
        $date     = Utilities::tryGetValueInsensitive(
            Resources::LAST_MODIFIED,
            $responseHeaders
        );
        $date     = Utilities::rfc1123ToDateTime($date);
        $result->setETag(Utilities::tryGetValueInsensitive(
            Resources::ETAG,
            $responseHeaders
        ));
        $result->setMetadata($metadata);
        $result->setLastModified($date);

        return $result;
    }
}
