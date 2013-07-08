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

namespace Aws\ImportExport\Iterator;

use Aws\Common\Iterator\AwsResourceIterator;
use Guzzle\Service\Resource\Model;

/**
 * Iterator for an ImportExport ListJobs command
 */
class ListJobsIterator extends AwsResourceIterator
{
    /**
     * {@inheritdoc}
     */
    protected function determineNextToken(Model $result)
    {
        $this->nextToken = null;

        if ($result->get($this->get('more_key'))) {
            $jobs = $result->get($this->get('result_key')) ?: array();
            $numJobs = count($jobs);
            $this->nextToken = $numJobs ? $jobs[$numJobs - 1]['JobId'] : null;
        }
    }
}
