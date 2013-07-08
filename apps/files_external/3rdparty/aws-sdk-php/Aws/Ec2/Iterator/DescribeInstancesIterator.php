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

namespace Aws\Ec2\Iterator;

use Aws\Common\Iterator\AwsResourceIterator;
use Guzzle\Service\Resource\Model;

/**
 * Iterator for an EC2 DescribeInstances command.
 *
 * This iterator inverts the typical structure of the DescribeIterators operation, such that the yielded items are the
 * actual instances, and the reservation is exposed as a property of the instance via the "Reservation" key.
 */
class DescribeInstancesIterator extends AwsResourceIterator
{
    /**
     * {@inheritdoc}
     */
    protected function handleResults(Model $result)
    {
        $instances = array();

        // Invert the structure so that instances are yielded and the reservation is exposed as a property
        foreach ($result->get('Reservations') as $reservation) {
            foreach ($reservation['Instances'] as $instance) {
                $instance['Reservation'] = $reservation;
                unset($instance['Reservation']['Instances']);
                $instances[] = $instance;
            }
        }

        return $instances;
    }
}
