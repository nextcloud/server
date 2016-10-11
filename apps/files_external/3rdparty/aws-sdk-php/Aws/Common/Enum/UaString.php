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
 * User-Agent header strings for various high level operations
 */
class UaString extends Enum
{
    /**
     * @var string Name of the option used to add to the UA string
     */
    const OPTION = 'ua.append';

    /**
     * @var string Resource iterator
     */
    const ITERATOR = 'ITR';

    /**
     * @var string Resource waiter
     */
    const WAITER = 'WTR';

    /**
     * @var string Session handlers (e.g. Amazon DynamoDB session handler)
     */
    const SESSION = 'SES';

    /**
     * @var string Multipart upload helper for Amazon S3
     */
    const MULTIPART_UPLOAD = 'MUP';

    /**
     * @var string Command executed during a batch transfer
     */
    const BATCH = 'BAT';
}
