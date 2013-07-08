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

namespace Aws\Sqs\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable MessageAttribute values
 */
class MessageAttribute extends Enum
{
    const ALL = 'All';
    const SENDER_ID = 'SenderId';
    const SENT_TIMESTAMP = 'SentTimestamp';
    const APPROXIMATE_RECEIVE_COUNT = 'ApproximateReceiveCount';
    const APPROXIMATE_FIRST_RECEIVE_TIMESTAMP = 'ApproximateFirstReceiveTimestamp';
}
