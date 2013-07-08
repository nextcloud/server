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
 * A base batch write request defining the ability to get the table name
 */
abstract class AbstractWriteRequest implements WriteRequestInterface
{
    /**
     * @var string The name of the DynamoDB table
     */
    protected $tableName;

    /**
     * {@inheritDoc}
     */
    public function getTableName()
    {
        return $this->tableName;
    }
}
