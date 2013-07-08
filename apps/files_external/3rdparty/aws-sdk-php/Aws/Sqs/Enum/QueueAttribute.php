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
 * Contains enumerable QueueAttribute values
 */
class QueueAttribute extends Enum
{
    const ALL = 'All';
    const POLICY = 'Policy';
    const VISIBILITY_TIMEOUT = 'VisibilityTimeout';
    const MAXIMUM_MESSAGE_SIZE = 'MaximumMessageSize';
    const MESSAGE_RETENTION_PERIOD = 'MessageRetentionPeriod';
    const APPROXIMATE_NUMBER_OF_MESSAGES = 'ApproximateNumberOfMessages';
    const APPROXIMATE_NUMBER_OF_MESSAGES_NOT_VISIBLE = 'ApproximateNumberOfMessagesNotVisible';
    const CREATED_TIMESTAMP = 'CreatedTimestamp';
    const LAST_MODIFIED_TIMESTAMP = 'LastModifiedTimestamp';
    const QUEUE_ARN = 'QueueArn';
    const APPROXIMATE_NUMBER_OF_MESSAGES_DELAYED = 'ApproximateNumberOfMessagesDelayed';
    const DELAY_SECONDS = 'DelaySeconds';
    const RECEIVE_MESSAGE_WAIT_TIME_SECONDS = 'ReceiveMessageWaitTimeSeconds';
}
