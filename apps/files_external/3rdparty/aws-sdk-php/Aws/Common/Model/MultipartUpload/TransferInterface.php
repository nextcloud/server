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

use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Service\Resource\Model;

/**
 * Interface for transferring the contents of a data source to an AWS service via a multipart upload interface
 */
interface TransferInterface extends HasDispatcherInterface
{
    /**
     * Upload the source to using a multipart upload
     *
     * @return Model|null Result of the complete multipart upload command or null if uploading was stopped
     */
    public function upload();

    /**
     * Abort the upload
     *
     * @return Model Returns the result of the abort multipart upload command
     */
    public function abort();

    /**
     * Get the current state of the upload
     *
     * @return TransferStateInterface
     */
    public function getState();

    /**
     * Stop the transfer and retrieve the current state.
     *
     * This allows you to stop and later resume a long running transfer if needed.
     *
     * @return TransferStateInterface
     */
    public function stop();

    /**
     * Set an option on the transfer object
     *
     * @param string $option Option to set
     * @param mixed  $value  The value to set
     *
     * @return self
     */
    public function setOption($option, $value);
}
