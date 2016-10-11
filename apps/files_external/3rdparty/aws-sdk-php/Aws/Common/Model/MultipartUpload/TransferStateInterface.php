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

/**
 * State of a multipart upload
 */
interface TransferStateInterface extends \Countable, \IteratorAggregate, \Serializable
{
    /**
     * Create the transfer state from the results of list parts request
     *
     * @param AwsClientInterface $client   Client used to send the request
     * @param UploadIdInterface  $uploadId Params needed to identify the upload and form the request
     *
     * @return self
     */
    public static function fromUploadId(AwsClientInterface $client, UploadIdInterface $uploadId);

    /**
     * Get the params used to identify an upload part
     *
     * @return UploadIdInterface
     */
    public function getUploadId();

    /**
     * Get the part information of a specific part
     *
     * @param int $partNumber Part to retrieve
     *
     * @return UploadPartInterface
     */
    public function getPart($partNumber);

    /**
     * Add a part to the transfer state
     *
     * @param UploadPartInterface $part The part to add
     *
     * @return self
     */
    public function addPart(UploadPartInterface $part);

    /**
     * Check if a specific part has been uploaded
     *
     * @param int $partNumber Part to check
     *
     * @return bool
     */
    public function hasPart($partNumber);

    /**
     * Get a list of all of the uploaded part numbers
     *
     * @return array
     */
    public function getPartNumbers();

    /**
     * Set whether or not the transfer has been aborted
     *
     * @param bool $aborted Set to true to mark the transfer as aborted
     *
     * @return self
     */
    public function setAborted($aborted);

    /**
     * Check if the transfer has been marked as aborted
     *
     * @return bool
     */
    public function isAborted();
}
