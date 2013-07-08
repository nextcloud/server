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

namespace Aws\DynamoDb\Enum;

use Aws\Common\Enum;

/**
 * Contains all enumerable DynamoDB attribute type values
 */
class Type extends Enum
{
    const S = 'S';
    const N = 'N';
    const B = 'B';

    const SS = 'SS';
    const NS = 'NS';
    const BS = 'BS';

    const STRING = 'S';
    const NUMBER = 'N';
    const BINARY = 'B';

    const STRING_SET = 'SS';
    const NUMBER_SET = 'NS';
    const BINARY_SET = 'BS';
}
