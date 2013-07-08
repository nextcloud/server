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

namespace Aws\Ec2\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable InstanceAttributeName values
 */
class InstanceAttributeName extends Enum
{
    const INSTANCE_TYPE = 'instanceType';
    const KERNEL = 'kernel';
    const RAMDISK = 'ramdisk';
    const USER_DATA = 'userData';
    const DISABLE_API_TERMINATION = 'disableApiTermination';
    const INSTANCE_INITIATED_SHUTDOWN_BEHAVIOR = 'instanceInitiatedShutdownBehavior';
    const ROOT_DEVICE_NAME = 'rootDeviceName';
    const BLOCK_DEVICE_MAPPING = 'blockDeviceMapping';
    const PRODUCT_CODES = 'productCodes';
    const SOURCE_DEST_CHECK = 'sourceDestCheck';
    const GROUP_SET = 'groupSet';
    const EBS_OPTIMIZED = 'ebsOptimized';
}
