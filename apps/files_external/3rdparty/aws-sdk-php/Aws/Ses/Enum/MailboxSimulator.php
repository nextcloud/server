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

namespace Aws\Ses\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable MailboxSimulator values
 */
class MailboxSimulator extends Enum
{
    const SUCCESS = 'success@simulator.amazonses.com';
    const BOUNCE = 'bounce@simulator.amazonses.com';
    const OUT_OF_THE_OFFICE = 'ooto@simulator.amazonses.com';
    const COMPLAINT = 'complaint@simulator.amazonses.com';
    const BLACKLIST = 'blacklist@simulator.amazonses.com';
}
