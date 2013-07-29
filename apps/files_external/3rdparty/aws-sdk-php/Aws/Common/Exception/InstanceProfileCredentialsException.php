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

use Aws\Common\Exception\RuntimeException;

/**
 * Exception thrown when an error occurs with instance profile credentials
 */
class InstanceProfileCredentialsException extends RuntimeException
{
    /**
     * @var string
     */
    protected $statusCode;

    /**
     * Set the error response code received from the instance metadata
     *
     * @param string $code Response code
     */
    public function setStatusCode($code)
    {
        $this->statusCode = $code;
    }

    /**
     * Get the error response code from the service
     *
     * @return string|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
