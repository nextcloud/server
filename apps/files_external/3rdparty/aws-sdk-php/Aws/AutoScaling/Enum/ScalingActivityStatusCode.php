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

namespace Aws\AutoScaling\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable ScalingActivityStatusCode values
 */
class ScalingActivityStatusCode extends Enum
{
    const WAITING_FOR_SPOT_INSTANCE_REQUEST_ID = 'WaitingForSpotInstanceRequestId';
    const WAITING_FOR_SPOT_INSTANCE_ID = 'WaitingForSpotInstanceId';
    const WAITING_FOR_INSTANCE_ID = 'WaitingForInstanceId';
    const PRE_IN_SERVICE = 'PreInService';
    const IN_PROGRESS = 'InProgress';
    const SUCCESSFUL = 'Successful';
    const FAILED = 'Failed';
    const CANCELLED = 'Cancelled';
}
