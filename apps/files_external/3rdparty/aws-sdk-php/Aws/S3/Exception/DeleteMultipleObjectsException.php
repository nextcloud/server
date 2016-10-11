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

namespace Aws\S3\Exception;

/**
 * Exception thrown when errors occur in a DeleteMultipleObjects request
 */
class DeleteMultipleObjectsException extends S3Exception
{
    /**
     * @var array Array of errors
     */
    protected $errors = array();

    /**
     * @param array $errors Array of errors
     */
    public function __construct(array $errors = array())
    {
        parent::__construct('Unable to delete certain keys when executing a DeleteMultipleObjects request');
        $this->errors = $errors;
    }

    /**
     * Get the errored objects
     *
     * @return array Returns an array of associative arrays, each containing
     *               a 'Code', 'Message', and 'Key' key.
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
