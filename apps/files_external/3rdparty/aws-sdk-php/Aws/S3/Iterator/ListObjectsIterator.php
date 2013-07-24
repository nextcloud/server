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
 * Iterator for an S3 ListObjects command
 *
 * This iterator includes the following additional options:
 * @option bool return_prefixes Set to true to receive both prefixes and objects in results
 * @option bool sort_results    Set to true to sort mixed (object/prefix) results
 * @option bool names_only      Set to true to receive only the object/prefix names
 */
class ListObjectsIterator extends AwsResourceIterator
{
    /**
     * {@inheritdoc}
     */
    protected function handleResults(Model $result)
    {
        // Get the list of objects and record the last key
        $objects = $result->get('Contents') ?: array();
        $numObjects = count($objects);
        $lastKey = $numObjects ? $objects[$numObjects - 1]['Key'] : false;
        if ($lastKey && !$result->hasKey($this->get('token_key'))) {
            $result->set($this->get('token_key'), $lastKey);
        }

        // Closure for getting the name of an object or prefix
        $getName = function ($object) {
            return isset($object['Key']) ? $object['Key'] : $object['Prefix'];
        };

        // If common prefixes returned (i.e. a delimiter was set) and they need to be returned, there is more to do
        if ($this->get('return_prefixes') && $result->hasKey('CommonPrefixes')) {
            // Collect and format the prefixes to include with the objects
            $objects = array_merge($objects, $result->get('CommonPrefixes'));

            // Sort the objects and prefixes to maintain alphabetical order, but only if some of each were returned
            if ($this->get('sort_results') && $lastKey && $objects) {
                usort($objects, function ($object1, $object2) use ($getName) {
                    return strcmp($getName($object1), $getName($object2));
                });
            }
        }

        // If only the names are desired, iterate through the results and convert the arrays to the object/prefix names
        if ($this->get('names_only')) {
            $objects = array_map($getName, $objects);
        }

        return $objects;
    }
}
