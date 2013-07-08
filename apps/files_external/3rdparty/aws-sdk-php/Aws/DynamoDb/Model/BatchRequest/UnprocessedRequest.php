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

namespace Aws\DynamoDb\Model\BatchRequest;

/**
 * Represents a batch delete request. It is composed of a table name and key
 */
class UnprocessedRequest extends AbstractWriteRequest
{
    /**
     * @var array The raw data from a batch write request's response
     */
    protected $data;

    /**
     * @param array  $data
     * @param string $tableName The name of the DynamoDB table
     */
    public function __construct(array $data, $tableName)
    {
        $this->data      = $data;
        $this->tableName = $tableName;
    }

    /**
     * The parameter form of the request
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}
