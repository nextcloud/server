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

namespace Aws\CloudSearch\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable SearchInstanceType values
 */
class SearchInstanceType extends Enum
{
    const SEARCH_INSTANCET1_MICRO = 'SearchInstance:t1.micro';
    const SEARCH_INSTANCEM1_SMALL = 'SearchInstance:m1.small';
    const SEARCH_INSTANCEM1_LARGE = 'SearchInstance:m1.large';
    const SEARCH_INSTANCEM2_XLARGE = 'SearchInstance:m2.xlarge';
}
