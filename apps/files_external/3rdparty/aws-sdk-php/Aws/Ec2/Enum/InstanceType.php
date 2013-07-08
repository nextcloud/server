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
 * Contains enumerable InstanceType values
 */
class InstanceType extends Enum
{
    const T1_MICRO = 't1.micro';
    const M1_SMALL = 'm1.small';
    const M1_MEDIUM = 'm1.medium';
    const M1_LARGE = 'm1.large';
    const M1_XLARGE = 'm1.xlarge';
    const M2_XLARGE = 'm2.xlarge';
    const M2_2XLARGE = 'm2.2xlarge';
    const M2_4XLARGE = 'm2.4xlarge';
    const M3_XLARGE = 'm3.xlarge';
    const M3_2XLARGE = 'm3.2xlarge';
    const C1_MEDIUM = 'c1.medium';
    const C1_XLARGE = 'c1.xlarge';
    const HI1_4XLARGE = 'hi1.4xlarge';
    const HS1_8XLARGE = 'hs1.8xlarge';
    const CC1_4XLARGE = 'cc1.4xlarge';
    const CC2_8XLARGE = 'cc2.8xlarge';
    const CG1_4XLARGE = 'cg1.4xlarge';
}
