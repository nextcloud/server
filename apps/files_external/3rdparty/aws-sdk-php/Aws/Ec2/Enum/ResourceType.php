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
 * Contains enumerable ResourceType values
 */
class ResourceType extends Enum
{
    const CUSTOMER_GATEWAY = 'customer-gateway';
    const DHCP_OPTIONS = 'dhcp-options';
    const IMAGE = 'image';
    const INSTANCE = 'instance';
    const SNAPSHOT = 'snapshot';
    const SPOT_INSTANCES_REQUEST = 'spot-instances-request';
    const SUBNET = 'subnet';
    const VOLUME = 'volume';
    const VPC = 'vpc';
    const VPN_CONNECTION = 'vpn-connection';
    const VPN_GATEWAY = 'vpn-gateway';
}
