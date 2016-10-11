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
 * Contains enumerable byte-size values
 */
class Size extends Enum
{
    const B         = 1;
    const BYTE      = 1;
    const BYTES     = 1;

    const KB        = 1024;
    const KILOBYTE  = 1024;
    const KILOBYTES = 1024;

    const MB        = 1048576;
    const MEGABYTE  = 1048576;
    const MEGABYTES = 1048576;

    const GB        = 1073741824;
    const GIGABYTE  = 1073741824;
    const GIGABYTES = 1073741824;

    const TB        = 1099511627776;
    const TERABYTE  = 1099511627776;
    const TERABYTES = 1099511627776;

    const PB        = 1125899906842624;
    const PETABYTE  = 1125899906842624;
    const PETABYTES = 1125899906842624;

    const EB        = 1152921504606846976;
    const EXABYTE   = 1152921504606846976;
    const EXABYTES  = 1152921504606846976;
}
