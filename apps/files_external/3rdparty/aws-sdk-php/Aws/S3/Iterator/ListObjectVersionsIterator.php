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
 * Iterator for an S3 ListObjectVersions command
 *
 * This iterator includes the following additional options:
 * @option bool return_prefixes Set to true to receive both prefixes and versions in results
 */
class ListObjectVersionsIterator extends AwsResourceIterator
{
    /**
     * {@inheritdoc}
     */
    protected function handleResults(Model $result)
    {
        // Get the list of object versions
        $versions = $result->get('Versions') ?: array();
        $deleteMarkers = $result->get('DeleteMarkers') ?: array();
        $versions = array_merge($versions, $deleteMarkers);

        // If there are prefixes and we want them, merge them in
        if ($this->get('return_prefixes') && $result->hasKey('CommonPrefixes')) {
            $versions = array_merge($versions, $result->get('CommonPrefixes'));
        }

        return $versions;
    }
}
