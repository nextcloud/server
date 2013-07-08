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

namespace Aws\Route53\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable RecordType values
 */
class RecordType extends Enum
{
    const SOA = 'SOA';
    const A = 'A';
    const TXT = 'TXT';
    const NS = 'NS';
    const CNAME = 'CNAME';
    const MX = 'MX';
    const PTR = 'PTR';
    const SRV = 'SRV';
    const SPF = 'SPF';
    const AAAA = 'AAAA';
}
