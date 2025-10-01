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

use Psr\Http\Message\StreamInterface;

/**
 * Holds result of GetBlob API.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class GetBlobResult
{
    private $properties;
    private $metadata;
    private $contentStream;

    /**
     * Creates GetBlobResult from getBlob call.
     *
     * @param array           $headers  The HTTP response headers.
     * @param StreamInterface $body     The response body.
     * @param array           $metadata The blob metadata.
     *
     * @internal
     *
     * @return GetBlobResult
     */
    public static function create(
        array $headers,
        StreamInterface $body,
        array $metadata
    ) {
        $result = new GetBlobResult();
        $result->setContentStream($body->detach());
        $result->setProperties(BlobProperties::createFromHttpHeaders($headers));
        $result->setMetadata(is_null($metadata) ? array() : $metadata);

        return $result;
    }

    /**
     * Gets blob metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Sets blob metadata.
     *
     * @param array $metadata value.
     *
     * @return void
     */
    protected function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Gets blob properties.
     *
     * @return BlobProperties
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Sets blob properties.
     *
     * @param BlobProperties $properties value.
     *
     * @return void
     */
    protected function setProperties(BlobProperties $properties)
    {
        $this->properties = $properties;
    }

    /**
     * Gets blob contentStream.
     *
     * @return resource
     */
    public function getContentStream()
    {
        return $this->contentStream;
    }

    /**
     * Sets blob contentStream.
     *
     * @param resource $contentStream The stream handle.
     *
     * @return void
     */
    protected function setContentStream($contentStream)
    {
        $this->contentStream = $contentStream;
    }
}
