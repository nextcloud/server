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

return array (
    'apiVersion' => '2012-11-05',
    'endpointPrefix' => 'sqs',
    'serviceFullName' => 'Amazon Simple Queue Service',
    'serviceAbbreviation' => 'Amazon SQS',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'Sqs',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sqs.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AddPermission' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The AddPermission action adds a permission to a queue for a specific principal. This allows for sharing access to the queue.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AddPermission',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Label' => array(
                    'required' => true,
                    'description' => 'The unique identification of the permission you\'re setting (e.g., AliceSendMessage). Constraints: Maximum 80 characters; alphanumeric characters, hyphens (-), and underscores (_) are allowed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AWSAccountIds' => array(
                    'required' => true,
                    'description' => 'The AWS account number of the principal who will be given permission. The principal must have an AWS account, but does not need to be signed up for Amazon SQS.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AWSAccountId',
                    'items' => array(
                        'name' => 'AWSAccountId',
                        'type' => 'string',
                    ),
                ),
                'Actions' => array(
                    'required' => true,
                    'description' => 'The action the client wants to allow for the specified principal.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ActionName',
                    'items' => array(
                        'name' => 'ActionName',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The operation that you requested would violate a limit. For example, ReceiveMessage returns this error if the maximum number of messages inflight has already been reached. AddPermission returns this error if the maximum number of permissions for the queue has already been reached.',
                    'class' => 'OverLimitException',
                ),
            ),
        ),
        'ChangeMessageVisibility' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The ChangeMessageVisibility action changes the visibility timeout of a specified message in a queue to a new value. The maximum allowed timeout value you can set the value to is 12 hours. This means you can\'t extend the timeout of a message in an existing queue to more than a total visibility timeout of 12 hours. (For more information visibility timeout, see Visibility Timeout in the Amazon SQS Developer Guide.)',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ChangeMessageVisibility',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ReceiptHandle' => array(
                    'required' => true,
                    'description' => 'The receipt handle associated with the message whose visibility timeout should be changed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'VisibilityTimeout' => array(
                    'required' => true,
                    'description' => 'The new value (in seconds) for the message\'s visibility timeout.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The message referred to is not in flight.',
                    'class' => 'MessageNotInflightException',
                ),
                array(
                    'reason' => 'The receipt handle provided is not valid.',
                    'class' => 'ReceiptHandleIsInvalidException',
                ),
            ),
        ),
        'ChangeMessageVisibilityBatch' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ChangeMessageVisibilityBatchResult',
            'responseType' => 'model',
            'summary' => 'This is a batch version of ChangeMessageVisibility. It takes multiple receipt handles and performs the operation on each of the them. The result of the operation on each message is reported individually in the response.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ChangeMessageVisibilityBatch',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Entries' => array(
                    'required' => true,
                    'description' => 'A list of receipt handles of the messages for which the visibility timeout must be changed.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ChangeMessageVisibilityBatchRequestEntry',
                    'items' => array(
                        'name' => 'ChangeMessageVisibilityBatchRequestEntry',
                        'description' => 'Encloses a receipt handle and an entry id for each message in ChangeMessageVisibilityBatchRequest.',
                        'type' => 'object',
                        'properties' => array(
                            'Id' => array(
                                'required' => true,
                                'description' => 'An identifier for this particular receipt handle. This is used to communicate the result. Note that the Ids of a batch request need to be unique within the request.',
                                'type' => 'string',
                            ),
                            'ReceiptHandle' => array(
                                'required' => true,
                                'description' => 'A receipt handle.',
                                'type' => 'string',
                            ),
                            'VisibilityTimeout' => array(
                                'description' => 'The new value (in seconds) for the message\'s visibility timeout.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Batch request contains more number of entries than permissible.',
                    'class' => 'TooManyEntriesInBatchRequestException',
                ),
                array(
                    'reason' => 'Batch request does not contain an entry.',
                    'class' => 'EmptyBatchRequestException',
                ),
                array(
                    'reason' => 'Two or more batch entries have the same Id in the request.',
                    'class' => 'BatchEntryIdsNotDistinctException',
                ),
                array(
                    'reason' => 'The Id of a batch entry in a batch request does not abide by the specification.',
                    'class' => 'InvalidBatchEntryIdException',
                ),
            ),
        ),
        'CreateQueue' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateQueueResult',
            'responseType' => 'model',
            'summary' => 'The CreateQueue action creates a new queue, or returns the URL of an existing one. When you request CreateQueue, you provide a name for the queue. To successfully create a new queue, you must provide a name that is unique within the scope of your own queues.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateQueue',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueName' => array(
                    'required' => true,
                    'description' => 'The name for the queue to be created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attributes' => array(
                    'description' => 'A map of attributes with their corresponding values.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'sentAs' => 'Attribute',
                    'additionalProperties' => array(
                        'description' => 'The name of a queue attribute.',
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'QueueAttributeName',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'You must wait 60 seconds after deleting a queue before you can create another with the same name.',
                    'class' => 'QueueDeletedRecentlyException',
                ),
                array(
                    'reason' => 'A queue already exists with this name. SQS returns this error only if the request includes attributes whose values differ from those of the existing queue.',
                    'class' => 'QueueNameExistsException',
                ),
            ),
        ),
        'DeleteMessage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The DeleteMessage action unconditionally removes the specified message from the specified queue. Even if the message is locked by another reader due to the visibility timeout setting, it is still deleted from the queue.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteMessage',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ReceiptHandle' => array(
                    'required' => true,
                    'description' => 'The receipt handle associated with the message to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The receipt handle is not valid for the current version.',
                    'class' => 'InvalidIdFormatException',
                ),
                array(
                    'reason' => 'The receipt handle provided is not valid.',
                    'class' => 'ReceiptHandleIsInvalidException',
                ),
            ),
        ),
        'DeleteMessageBatch' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DeleteMessageBatchResult',
            'responseType' => 'model',
            'summary' => 'This is a batch version of DeleteMessage. It takes multiple receipt handles and deletes each one of the messages. The result of the delete operation on each message is reported individually in the response.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteMessageBatch',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Entries' => array(
                    'required' => true,
                    'description' => 'A list of receipt handles for the messages to be deleted.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'DeleteMessageBatchRequestEntry',
                    'items' => array(
                        'name' => 'DeleteMessageBatchRequestEntry',
                        'description' => 'Encloses a receipt handle and an identifier for it.',
                        'type' => 'object',
                        'properties' => array(
                            'Id' => array(
                                'required' => true,
                                'description' => 'An identifier for this particular receipt handle. This is used to communicate the result. Note that the Ids of a batch request need to be unique within the request.',
                                'type' => 'string',
                            ),
                            'ReceiptHandle' => array(
                                'required' => true,
                                'description' => 'A receipt handle.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Batch request contains more number of entries than permissible.',
                    'class' => 'TooManyEntriesInBatchRequestException',
                ),
                array(
                    'reason' => 'Batch request does not contain an entry.',
                    'class' => 'EmptyBatchRequestException',
                ),
                array(
                    'reason' => 'Two or more batch entries have the same Id in the request.',
                    'class' => 'BatchEntryIdsNotDistinctException',
                ),
                array(
                    'reason' => 'The Id of a batch entry in a batch request does not abide by the specification.',
                    'class' => 'InvalidBatchEntryIdException',
                ),
            ),
        ),
        'DeleteQueue' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This action unconditionally deletes the queue specified by the queue URL. Use this operation WITH CARE! The queue is deleted even if it is NOT empty.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteQueue',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'GetQueueAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetQueueAttributesResult',
            'responseType' => 'model',
            'summary' => 'Gets attributes for the specified queue. The following attributes are supported: All - returns all values. ApproximateNumberOfMessages - returns the approximate number of visible messages in a queue. For more information, see Resources Required to Process Messages in the Amazon SQS Developer Guide. ApproximateNumberOfMessagesNotVisible - returns the approximate number of messages that are not timed-out and not deleted. For more information, see Resources Required to Process Messages in the Amazon SQS Developer Guide. VisibilityTimeout - returns the visibility timeout for the queue. For more information about visibility timeout, see Visibility Timeout in the Amazon SQS Developer Guide. CreatedTimestamp - returns the time when the queue was created (epoch time in seconds). LastModifiedTimestamp - returns the time when the queue was last changed (epoch time in seconds). Policy - returns the queue\'s policy. MaximumMessageSize - returns the limit of how many bytes a message can contain before Amazon SQS rejects it. MessageRetentionPeriod - returns the number of seconds Amazon SQS retains a message. QueueArn - returns the queue\'s Amazon resource name (ARN). ApproximateNumberOfMessagesDelayed - returns the approximate number of messages that are pending to be added to the queue. DelaySeconds - returns the default delay on the queue in seconds. ReceiveMessageWaitTimeSeconds - returns the time for which a ReceiveMessage call will wait for a message to arrive.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetQueueAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AttributeNames' => array(
                    'description' => 'A list of attributes to retrieve information for.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AttributeName',
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                        'enum' => array(
                            'All',
                            'Policy',
                            'VisibilityTimeout',
                            'MaximumMessageSize',
                            'MessageRetentionPeriod',
                            'ApproximateNumberOfMessages',
                            'ApproximateNumberOfMessagesNotVisible',
                            'CreatedTimestamp',
                            'LastModifiedTimestamp',
                            'QueueArn',
                            'ApproximateNumberOfMessagesDelayed',
                            'DelaySeconds',
                            'ReceiveMessageWaitTimeSeconds',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The attribute referred to does not exist.',
                    'class' => 'InvalidAttributeNameException',
                ),
            ),
        ),
        'GetQueueUrl' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetQueueUrlResult',
            'responseType' => 'model',
            'summary' => 'The GetQueueUrl action returns the URL of an existing queue.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetQueueUrl',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueName' => array(
                    'required' => true,
                    'description' => 'The name of the queue whose URL must be fetched.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'QueueOwnerAWSAccountId' => array(
                    'description' => 'The AWS account number of the queue\'s owner.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The queue referred to does not exist.',
                    'class' => 'QueueDoesNotExistException',
                ),
            ),
        ),
        'ListQueues' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListQueuesResult',
            'responseType' => 'model',
            'summary' => 'Returns a list of your queues.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListQueues',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueNamePrefix' => array(
                    'description' => 'A string to use for filtering the list results. Only those queues whose name begins with the specified string are returned.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ReceiveMessage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReceiveMessageResult',
            'responseType' => 'model',
            'summary' => 'Retrieves one or more messages from the specified queue, including the message body and message ID of each message. Messages returned by this action stay in the queue until you delete them. However, once a message is returned to a ReceiveMessage request, it is not returned on subsequent ReceiveMessage requests for the duration of the VisibilityTimeout. If you do not specify a VisibilityTimeout in the request, the overall visibility timeout for the queue is used for the returned messages.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ReceiveMessage',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AttributeNames' => array(
                    'description' => 'A list of attributes that need to be returned along with each message. The set of valid attributes are [SenderId, ApproximateFirstReceiveTimestamp, ApproximateReceiveCount, SentTimestamp].',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AttributeName',
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
                'MaxNumberOfMessages' => array(
                    'description' => 'The maximum number of messages to return. Amazon SQS never returns more messages than this value but may return fewer.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'VisibilityTimeout' => array(
                    'description' => 'The duration (in seconds) that the received messages are hidden from subsequent retrieve requests after being retrieved by a ReceiveMessage request.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'WaitTimeSeconds' => array(
                    'description' => 'The duration (in seconds) for which the call will wait for a message to arrive in the queue before returning. If a message is available, the call will return sooner than WaitTimeSeconds.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The operation that you requested would violate a limit. For example, ReceiveMessage returns this error if the maximum number of messages inflight has already been reached. AddPermission returns this error if the maximum number of permissions for the queue has already been reached.',
                    'class' => 'OverLimitException',
                ),
            ),
        ),
        'RemovePermission' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The RemovePermission action revokes any permissions in the queue policy that matches the specified Label parameter. Only the owner of the queue can remove permissions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RemovePermission',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Label' => array(
                    'required' => true,
                    'description' => 'The identification of the permission to remove. This is the label added with the AddPermission operation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'SendMessage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SendMessageResult',
            'responseType' => 'model',
            'summary' => 'The SendMessage action delivers a message to the specified queue.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SendMessage',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MessageBody' => array(
                    'required' => true,
                    'description' => 'The message to send.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DelaySeconds' => array(
                    'description' => 'The number of seconds the message has to be delayed.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The message contains characters outside the allowed set.',
                    'class' => 'InvalidMessageContentsException',
                ),
            ),
        ),
        'SendMessageBatch' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SendMessageBatchResult',
            'responseType' => 'model',
            'summary' => 'This is a batch version of SendMessage. It takes multiple messages and adds each of them to the queue. The result of each add operation is reported individually in the response.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SendMessageBatch',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Entries' => array(
                    'required' => true,
                    'description' => 'A list of SendMessageBatchRequestEntrys.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SendMessageBatchRequestEntry',
                    'items' => array(
                        'name' => 'SendMessageBatchRequestEntry',
                        'description' => 'Contains the details of a single SQS message along with a Id.',
                        'type' => 'object',
                        'properties' => array(
                            'Id' => array(
                                'required' => true,
                                'description' => 'An identifier for the message in this batch. This is used to communicate the result. Note that the the Ids of a batch request need to be unique within the request.',
                                'type' => 'string',
                            ),
                            'MessageBody' => array(
                                'required' => true,
                                'description' => 'Body of the message.',
                                'type' => 'string',
                            ),
                            'DelaySeconds' => array(
                                'description' => 'The number of seconds for which the message has to be delayed.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Batch request contains more number of entries than permissible.',
                    'class' => 'TooManyEntriesInBatchRequestException',
                ),
                array(
                    'reason' => 'Batch request does not contain an entry.',
                    'class' => 'EmptyBatchRequestException',
                ),
                array(
                    'reason' => 'Two or more batch entries have the same Id in the request.',
                    'class' => 'BatchEntryIdsNotDistinctException',
                ),
                array(
                    'reason' => 'The length of all the messages put together is more than the limit.',
                    'class' => 'BatchRequestTooLongException',
                ),
                array(
                    'reason' => 'The Id of a batch entry in a batch request does not abide by the specification.',
                    'class' => 'InvalidBatchEntryIdException',
                ),
            ),
        ),
        'SetQueueAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Sets the value of one or more queue attributes. Valid attributes that can be set are [VisibilityTimeout, Policy, MaximumMessageSize, MessageRetentionPeriod, ReceiveMessageWaitTimeSeconds].',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetQueueAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-05',
                ),
                'QueueUrl' => array(
                    'required' => true,
                    'description' => 'The URL of the SQS queue to take action on.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attributes' => array(
                    'required' => true,
                    'description' => 'A map of attributes to set.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'sentAs' => 'Attribute',
                    'additionalProperties' => array(
                        'description' => 'The name of a queue attribute.',
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'QueueAttributeName',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The attribute referred to does not exist.',
                    'class' => 'InvalidAttributeNameException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'ChangeMessageVisibilityBatchResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Successful' => array(
                    'description' => 'A list of ChangeMessageVisibilityBatchResultEntrys.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'ChangeMessageVisibilityBatchResultEntry',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'ChangeMessageVisibilityBatchResultEntry',
                        'description' => 'Encloses the id of an entry in ChangeMessageVisibilityBatchRequest.',
                        'type' => 'object',
                        'sentAs' => 'ChangeMessageVisibilityBatchResultEntry',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'Represents a message whose visibility timeout has been changed successfully.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Failed' => array(
                    'description' => 'A list of BatchResultErrorEntrys.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'BatchResultErrorEntry',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'BatchResultErrorEntry',
                        'description' => 'This is used in the responses of batch API to give a detailed description of the result of an operation on each entry in the request.',
                        'type' => 'object',
                        'sentAs' => 'BatchResultErrorEntry',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The id of an entry in a batch request.',
                                'type' => 'string',
                            ),
                            'SenderFault' => array(
                                'description' => 'Whether the error happened due to the sender\'s fault.',
                                'type' => 'boolean',
                            ),
                            'Code' => array(
                                'description' => 'An error code representing why the operation failed on this entry.',
                                'type' => 'string',
                            ),
                            'Message' => array(
                                'description' => 'A message explaining why the operation failed on this entry.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateQueueResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'QueueUrl' => array(
                    'description' => 'The URL for the created SQS queue.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DeleteMessageBatchResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Successful' => array(
                    'description' => 'A list of DeleteMessageBatchResultEntrys.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'DeleteMessageBatchResultEntry',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'DeleteMessageBatchResultEntry',
                        'description' => 'Encloses the id an entry in DeleteMessageBatchRequest.',
                        'type' => 'object',
                        'sentAs' => 'DeleteMessageBatchResultEntry',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'Represents a successfully deleted message.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Failed' => array(
                    'description' => 'A list of BatchResultErrorEntrys.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'BatchResultErrorEntry',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'BatchResultErrorEntry',
                        'description' => 'This is used in the responses of batch API to give a detailed description of the result of an operation on each entry in the request.',
                        'type' => 'object',
                        'sentAs' => 'BatchResultErrorEntry',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The id of an entry in a batch request.',
                                'type' => 'string',
                            ),
                            'SenderFault' => array(
                                'description' => 'Whether the error happened due to the sender\'s fault.',
                                'type' => 'boolean',
                            ),
                            'Code' => array(
                                'description' => 'An error code representing why the operation failed on this entry.',
                                'type' => 'string',
                            ),
                            'Message' => array(
                                'description' => 'A message explaining why the operation failed on this entry.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'GetQueueAttributesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Attributes' => array(
                    'description' => 'A map of attributes to the respective values.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Attribute',
                    'data' => array(
                        'xmlFlattened' => true,
                        'xmlMap' => array(
                            'Policy',
                            'VisibilityTimeout',
                            'MaximumMessageSize',
                            'MessageRetentionPeriod',
                            'ApproximateNumberOfMessages',
                            'ApproximateNumberOfMessagesNotVisible',
                            'CreatedTimestamp',
                            'LastModifiedTimestamp',
                            'QueueArn',
                            'ApproximateNumberOfMessagesDelayed',
                            'DelaySeconds',
                            'ReceiveMessageWaitTimeSeconds',
                        ),
                    ),
                    'filters' => array(
                        array(
                            'method' => 'Aws\\Common\\Command\\XmlResponseLocationVisitor::xmlMap',
                            'args' => array(
                                '@value',
                                'Attribute',
                                'Name',
                                'Value',
                            ),
                        ),
                    ),
                    'items' => array(
                        'name' => 'Attribute',
                        'type' => 'object',
                        'sentAs' => 'Attribute',
                        'additionalProperties' => true,
                        'properties' => array(
                            'Name' => array(
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The value of a queue attribute.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                    'additionalProperties' => false,
                ),
            ),
        ),
        'GetQueueUrlResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'QueueUrl' => array(
                    'description' => 'The URL for the queue.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListQueuesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'QueueUrls' => array(
                    'description' => 'A list of queue URLs, up to 1000 entries.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'QueueUrl',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'QueueUrl',
                        'type' => 'string',
                        'sentAs' => 'QueueUrl',
                    ),
                ),
            ),
        ),
        'ReceiveMessageResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Messages' => array(
                    'description' => 'A list of messages.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Message',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'Message',
                        'type' => 'object',
                        'sentAs' => 'Message',
                        'properties' => array(
                            'MessageId' => array(
                                'type' => 'string',
                            ),
                            'ReceiptHandle' => array(
                                'type' => 'string',
                            ),
                            'MD5OfBody' => array(
                                'type' => 'string',
                            ),
                            'Body' => array(
                                'type' => 'string',
                            ),
                            'Attributes' => array(
                                'type' => 'array',
                                'sentAs' => 'Attribute',
                                'data' => array(
                                    'xmlFlattened' => true,
                                    'xmlMap' => array(
                                        'Policy',
                                        'VisibilityTimeout',
                                        'MaximumMessageSize',
                                        'MessageRetentionPeriod',
                                        'ApproximateNumberOfMessages',
                                        'ApproximateNumberOfMessagesNotVisible',
                                        'CreatedTimestamp',
                                        'LastModifiedTimestamp',
                                        'QueueArn',
                                        'ApproximateNumberOfMessagesDelayed',
                                        'DelaySeconds',
                                        'ReceiveMessageWaitTimeSeconds',
                                    ),
                                ),
                                'filters' => array(
                                    array(
                                        'method' => 'Aws\\Common\\Command\\XmlResponseLocationVisitor::xmlMap',
                                        'args' => array(
                                            '@value',
                                            'Attribute',
                                            'Name',
                                            'Value',
                                        ),
                                    ),
                                ),
                                'items' => array(
                                    'name' => 'Attribute',
                                    'type' => 'object',
                                    'sentAs' => 'Attribute',
                                    'additionalProperties' => true,
                                    'properties' => array(
                                        'Name' => array(
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'The value of a queue attribute.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                                'additionalProperties' => false,
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'SendMessageResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'MD5OfMessageBody' => array(
                    'description' => 'An MD5 digest of the non-URL-encoded message body string. This can be used to verify that SQS received the message correctly. SQS first URL decodes the message before creating the MD5 digest. For information about MD5, go to http://faqs.org/rfcs/rfc1321.html.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MessageId' => array(
                    'description' => 'The message ID of the message added to the queue.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'SendMessageBatchResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Successful' => array(
                    'description' => 'A list of SendMessageBatchResultEntrys.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'SendMessageBatchResultEntry',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'SendMessageBatchResultEntry',
                        'description' => 'Encloses a message ID for successfully enqueued message of a SendMessageBatchRequest.',
                        'type' => 'object',
                        'sentAs' => 'SendMessageBatchResultEntry',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'An identifier for the message in this batch.',
                                'type' => 'string',
                            ),
                            'MessageId' => array(
                                'description' => 'An identifier for the message.',
                                'type' => 'string',
                            ),
                            'MD5OfMessageBody' => array(
                                'description' => 'An MD5 digest of the non-URL-encoded message body string. This can be used to verify that SQS received the message correctly. SQS first URL decodes the message before creating the MD5 digest. For information about MD5, go to http://faqs.org/rfcs/rfc1321.html.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Failed' => array(
                    'description' => 'A list of BatchResultErrorEntrys with the error detail about each message that could not be enqueued.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'BatchResultErrorEntry',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'name' => 'BatchResultErrorEntry',
                        'description' => 'This is used in the responses of batch API to give a detailed description of the result of an operation on each entry in the request.',
                        'type' => 'object',
                        'sentAs' => 'BatchResultErrorEntry',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The id of an entry in a batch request.',
                                'type' => 'string',
                            ),
                            'SenderFault' => array(
                                'description' => 'Whether the error happened due to the sender\'s fault.',
                                'type' => 'boolean',
                            ),
                            'Code' => array(
                                'description' => 'An error code representing why the operation failed on this entry.',
                                'type' => 'string',
                            ),
                            'Message' => array(
                                'description' => 'A message explaining why the operation failed on this entry.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'ListQueues' => array(
                'result_key' => 'QueueUrls',
            ),
        ),
    ),
);
