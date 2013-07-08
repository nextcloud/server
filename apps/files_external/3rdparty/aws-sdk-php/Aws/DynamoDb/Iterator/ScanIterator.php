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

namespace Aws\DynamoDb\Iterator;

use Aws\Common\Iterator\AwsResourceIterator;
use Guzzle\Service\Resource\Model;

/**
 * Iterator for a DynamoDB Scan operation. Can also get the total scanned count
 */
class ScanIterator extends AwsResourceIterator
{
    /**
     * @var int Total number of scanned items
     */
    protected $scannedCount = 0;

    /**
     * Get the total number of scanned items
     *
     * @return int
     */
    public function getScannedCount()
    {
        return $this->scannedCount;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleResults(Model $result)
    {
        $this->scannedCount += (int) $result->get('ScannedCount');

        return parent::handleResults($result);
    }
}
