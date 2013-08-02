<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\Common\Model\MultipartUpload;

use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Http\EntityBody;

/**
 * Easily create a multipart uploader used to quickly and reliably upload a
 * large file or data stream to Amazon S3 using multipart uploads
 */
abstract class AbstractUploadBuilder
{
    /**
     * @var AwsClientInterface Client used to transfer requests
     */
    protected $client;

    /**
     * @var TransferStateInterface State of the transfer
     */
    protected $state;

    /**
     * @var EntityBody Source of the data
     */
    protected $source;

    /**
     * @var array Array of headers to set on the object
     */
    protected $headers = array();

    /**
     * Return a new instance of the UploadBuilder
     *
     * @return self
     */
    public static function newInstance()
    {
        return new static;
    }

    /**
     * Set the client used to connect to the AWS service
     *
     * @param AwsClientInterface $client Client to use
     *
     * @return self
     */
    public function setClient(AwsClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Set the state of the upload. This is useful for resuming from a previously started multipart upload.
     * You must use a local file stream as the data source if you wish to resume from a previous upload.
     *
     * @param TransferStateInterface|string $state Pass a TransferStateInterface object or the ID of the initiated
     *                                             multipart upload. When an ID is passed, the builder will create a
     *                                             state object using the data from a ListParts API response.
     *
     * @return self
     */
    public function resumeFrom($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Set the data source of the transfer
     *
     * @param resource|string|EntityBody $source Source of the transfer. Pass a string to transfer from a file on disk.
     *                                           You can also stream from a resource returned from fopen or a Guzzle
     *                                           {@see EntityBody} object.
     *
     * @return self
     * @throws InvalidArgumentException when the source cannot be found or opened
     */
    public function setSource($source)
    {
        // Use the contents of a file as the data source
        if (is_string($source)) {
            if (!file_exists($source)) {
                throw new InvalidArgumentException("File does not exist: {$source}");
            }
            // Clear the cache so that we send accurate file sizes
            clearstatcache(true, $source);
            $source = fopen($source, 'r');
        }

        $this->source = EntityBody::factory($source);

        if ($this->source->isSeekable() && $this->source->getSize() == 0) {
            throw new InvalidArgumentException('Empty body provided to upload builder');
        }

        return $this;
    }

    /**
     * Specify the headers to set on the upload
     *
     * @param array $headers Headers to add to the uploaded object
     *
     * @return self
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Build the appropriate uploader based on the builder options
     *
     * @return TransferInterface
     */
    abstract public function build();

    /**
     * Initiate the multipart upload
     *
     * @return TransferStateInterface
     */
    abstract protected function initiateMultipartUpload();
}
