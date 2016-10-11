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

namespace Aws\Common\Exception;

use Aws\Common\Model\MultipartUpload\TransferStateInterface;

/**
 * Thrown when a {@see Aws\Common\MultipartUpload\TransferInterface} object encounters an error during transfer
 */
class MultipartUploadException extends RuntimeException
{
    /**
     * @var TransferStateInterface State of the transfer when the error was encountered
     */
    protected $state;

    /**
     * @param TransferStateInterface $state     Transfer state
     * @param \Exception             $exception Last encountered exception
     */
    public function __construct(TransferStateInterface $state, \Exception $exception = null)
    {
        parent::__construct(
            'An error was encountered while performing a multipart upload: ' . $exception->getMessage(),
            0,
            $exception
        );

        $this->state = $state;
    }

    /**
     * Get the state of the transfer
     *
     * @return TransferStateInterface
     */
    public function getState()
    {
        return $this->state;
    }
}
