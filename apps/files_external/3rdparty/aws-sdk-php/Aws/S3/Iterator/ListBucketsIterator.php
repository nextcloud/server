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

namespace Aws\S3\Iterator;

use Aws\Common\Iterator\AwsResourceIterator;
use Guzzle\Service\Resource\Model;

/**
 * Iterator for the S3 ListBuckets command
 *
 * This iterator includes the following additional options:
 *
 * - names_only: Set to true to receive only the object/prefix names
 */
class ListBucketsIterator extends AwsResourceIterator
{
    /**
     * {@inheritdoc}
     */
    protected function handleResults(Model $result)
    {
        // Get the results
        $buckets = $result->get('Buckets') ?: array();

        // If only the names_only set, change arrays to a string
        if ($this->get('names_only')) {
            foreach ($buckets as &$bucket) {
                $bucket = $bucket['Name'];
            }
        }

        return $buckets;
    }
}
