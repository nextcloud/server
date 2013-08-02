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

namespace Aws\Common\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable time values
 */
class Time extends Enum
{
    const SECOND  = 1;
    const SECONDS = 1;

    const MINUTE  = 60;
    const MINUTES = 60;

    const HOUR    = 3600;
    const HOURS   = 3600;

    const DAY     = 86400;
    const DAYS    = 86400;

    const WEEK    = 604800;
    const WEEKS   = 604800;

    const MONTH   = 2592000;
    const MONTHS  = 2592000;

    const YEAR    = 31557600;
    const YEARS   = 31557600;
}
