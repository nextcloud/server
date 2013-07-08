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
    'apiVersion' => '2010-03-31',
    'endpointPrefix' => 'sns',
    'serviceFullName' => 'Amazon Simple Notification Service',
    'serviceAbbreviation' => 'Amazon SNS',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'Sns',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'sns.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AddPermission' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The AddPermission action adds a statement to a topic\'s access control policy, granting access for the specified AWS accounts to the specified actions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AddPermission',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the topic whose access control policy you wish to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Label' => array(
                    'required' => true,
                    'description' => 'A unique identifier for the new policy statement.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AWSAccountId' => array(
                    'required' => true,
                    'description' => 'The AWS account IDs of the users (principals) who will be given access to the specified actions. The users must have AWS accounts, but do not need to be signed up for this service.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AWSAccountId.member',
                    'items' => array(
                        'name' => 'delegate',
                        'type' => 'string',
                    ),
                ),
                'ActionName' => array(
                    'required' => true,
                    'description' => 'The action you want to allow for the specified principal(s).',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ActionName.member',
                    'items' => array(
                        'name' => 'action',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
            ),
        ),
        'ConfirmSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ConfirmSubscriptionResponse',
            'responseType' => 'model',
            'summary' => 'The ConfirmSubscription action verifies an endpoint owner\'s intent to receive messages by validating the token sent to the endpoint by an earlier Subscribe action. If the token is valid, the action creates a new subscription and returns its Amazon Resource Name (ARN). This call requires an AWS signature only when the AuthenticateOnUnsubscribe flag is set to "true".',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ConfirmSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the topic for which you wish to confirm a subscription.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Token' => array(
                    'required' => true,
                    'description' => 'Short-lived token sent to an endpoint during the Subscribe action.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AuthenticateOnUnsubscribe' => array(
                    'description' => 'Disallows unauthenticated unsubscribes of the subscription. If the value of this parameter is true and the request has an AWS signature, then only the topic owner and the subscription owner can unsubscribe the endpoint. The unsubscribe action requires AWS authentication.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that the customer already owns the maximum allowed number of subscriptions.',
                    'class' => 'SubscriptionLimitExceededException',
                ),
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'CreateTopic' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateTopicResponse',
            'responseType' => 'model',
            'summary' => 'The CreateTopic action creates a topic to which notifications can be published. Users can create at most 100 topics. For more information, see http://aws.amazon.com/sns. This action is idempotent, so if the requester already owns a topic with the specified name, that topic\'s ARN is returned without creating a new topic.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateTopic',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'Name' => array(
                    'required' => true,
                    'description' => 'The name of the topic you want to create.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates that the customer already owns the maximum allowed number of topics.',
                    'class' => 'TopicLimitExceededException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'DeleteTopic' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The DeleteTopic action deletes a topic and all its subscriptions. Deleting a topic might prevent some messages previously sent to the topic from being delivered to subscribers. This action is idempotent, so deleting a topic that does not exist does not result in an error.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteTopic',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the topic you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
            ),
        ),
        'GetSubscriptionAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetSubscriptionAttributesResponse',
            'responseType' => 'model',
            'summary' => 'The GetSubscriptionAttribtues action returns all of the properties of a subscription.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetSubscriptionAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'SubscriptionArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the subscription whose properties you want to get.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'GetTopicAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetTopicAttributesResponse',
            'responseType' => 'model',
            'summary' => 'The GetTopicAttributes action returns all of the properties of a topic. Topic properties returned might differ based on the authorization of the user.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetTopicAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the topic whose properties you want to get.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'ListSubscriptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListSubscriptionsResponse',
            'responseType' => 'model',
            'summary' => 'The ListSubscriptions action returns a list of the requester\'s subscriptions. Each call returns a limited list of subscriptions, up to 100. If there are more subscriptions, a NextToken is also returned. Use the NextToken parameter in a new ListSubscriptions call to get further results.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListSubscriptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'NextToken' => array(
                    'description' => 'Token returned by the previous ListSubscriptions request.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'ListSubscriptionsByTopic' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListSubscriptionsByTopicResponse',
            'responseType' => 'model',
            'summary' => 'The ListSubscriptionsByTopic action returns a list of the subscriptions to a specific topic. Each call returns a limited list of subscriptions, up to 100. If there are more subscriptions, a NextToken is also returned. Use the NextToken parameter in a new ListSubscriptionsByTopic call to get further results.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListSubscriptionsByTopic',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the topic for which you wish to find subscriptions.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'description' => 'Token returned by the previous ListSubscriptionsByTopic request.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'ListTopics' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListTopicsResponse',
            'responseType' => 'model',
            'summary' => 'The ListTopics action returns a list of the requester\'s topics. Each call returns a limited list of topics, up to 100. If there are more topics, a NextToken is also returned. Use the NextToken parameter in a new ListTopics call to get further results.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListTopics',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'NextToken' => array(
                    'description' => 'Token returned by the previous ListTopics request.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'Publish' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'PublishResponse',
            'responseType' => 'model',
            'summary' => 'The Publish action sends a message to all of a topic\'s subscribed endpoints. When a messageId is returned, the message has been saved and Amazon SNS will attempt to deliver it to the topic\'s subscribers shortly. The format of the outgoing message to each subscribed endpoint depends on the notification protocol selected.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'Publish',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The topic you want to publish to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Message' => array(
                    'required' => true,
                    'description' => 'The message you want to send to the topic.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Subject' => array(
                    'description' => 'Optional parameter to be used as the "Subject" line when the message is delivered to email endpoints. This field will also be included, if present, in the standard JSON messages delivered to other endpoints.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MessageStructure' => array(
                    'description' => 'Set MessageStructure to json if you want to send a different message for each protocol. For example, using one publish action, you can send a short message to your SMS subscribers and a longer message to your email subscribers. If you set MessageStructure to json, the value of the Message parameter must:',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'RemovePermission' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The RemovePermission action removes a statement from a topic\'s access control policy.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RemovePermission',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the topic whose access control policy you wish to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Label' => array(
                    'required' => true,
                    'description' => 'The unique label of the statement you want to remove.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
            ),
        ),
        'SetSubscriptionAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The SetSubscriptionAttributes action allows a subscription owner to set an attribute of the topic to a new value.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetSubscriptionAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'SubscriptionArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the subscription to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AttributeName' => array(
                    'required' => true,
                    'description' => 'The name of the attribute you want to set. Only a subset of the subscriptions attributes are mutable.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AttributeValue' => array(
                    'description' => 'The new value for the attribute in JSON format.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'SetTopicAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The SetTopicAttributes action allows a topic owner to set an attribute of the topic to a new value.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetTopicAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the topic to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AttributeName' => array(
                    'required' => true,
                    'description' => 'The name of the attribute you want to set. Only a subset of the topic\'s attributes are mutable.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AttributeValue' => array(
                    'description' => 'The new value for the attribute.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'Subscribe' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SubscribeResponse',
            'responseType' => 'model',
            'summary' => 'The Subscribe action prepares to subscribe an endpoint by sending the endpoint a confirmation message. To actually create a subscription, the endpoint owner must call the ConfirmSubscription action with the token from the confirmation message. Confirmation tokens are valid for three days.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'Subscribe',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'TopicArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the topic you want to subscribe to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Protocol' => array(
                    'required' => true,
                    'description' => 'The protocol you want to use. Supported protocols include:',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Endpoint' => array(
                    'description' => 'The endpoint that you want to receive notifications. Endpoints vary by protocol:',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that the customer already owns the maximum allowed number of subscriptions.',
                    'class' => 'SubscriptionLimitExceededException',
                ),
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
            ),
        ),
        'Unsubscribe' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The Unsubscribe action deletes a subscription. If the subscription requires authentication for deletion, only the owner of the subscription or the topic\'s owner can unsubscribe, and an AWS signature is required. If the Unsubscribe call does not require authentication and the requester is not the subscription owner, a final cancellation message is delivered to the endpoint, so that the endpoint owner can easily resubscribe to the topic if the Unsubscribe request was unintended.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'Unsubscribe',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-03-31',
                ),
                'SubscriptionArn' => array(
                    'required' => true,
                    'description' => 'The ARN of the subscription to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request parameter does not comply with the associated constraints.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'Indicates an internal service error.',
                    'class' => 'InternalErrorException',
                ),
                array(
                    'reason' => 'Indicates that the user has been denied access to the requested resource.',
                    'class' => 'AuthorizationErrorException',
                ),
                array(
                    'reason' => 'Indicates that the requested resource does not exist.',
                    'class' => 'NotFoundException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'ConfirmSubscriptionResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SubscriptionArn' => array(
                    'description' => 'The ARN of the created subscription.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'CreateTopicResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TopicArn' => array(
                    'description' => 'The Amazon Resource Name (ARN) assigned to the created topic.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'GetSubscriptionAttributesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Attributes' => array(
                    'description' => 'A map of the subscription\'s attributes. Attributes in this map include the following:',
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlMap' => array(
                        ),
                    ),
                    'filters' => array(
                        array(
                            'method' => 'Aws\\Common\\Command\\XmlResponseLocationVisitor::xmlMap',
                            'args' => array(
                                '@value',
                                'entry',
                                'key',
                                'value',
                            ),
                        ),
                    ),
                    'items' => array(
                        'name' => 'entry',
                        'type' => 'object',
                        'sentAs' => 'entry',
                        'additionalProperties' => true,
                        'properties' => array(
                            'key' => array(
                                'type' => 'string',
                            ),
                            'value' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                    'additionalProperties' => false,
                ),
            ),
        ),
        'GetTopicAttributesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Attributes' => array(
                    'description' => 'A map of the topic\'s attributes. Attributes in this map include the following:',
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlMap' => array(
                        ),
                    ),
                    'filters' => array(
                        array(
                            'method' => 'Aws\\Common\\Command\\XmlResponseLocationVisitor::xmlMap',
                            'args' => array(
                                '@value',
                                'entry',
                                'key',
                                'value',
                            ),
                        ),
                    ),
                    'items' => array(
                        'name' => 'entry',
                        'type' => 'object',
                        'sentAs' => 'entry',
                        'additionalProperties' => true,
                        'properties' => array(
                            'key' => array(
                                'type' => 'string',
                            ),
                            'value' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                    'additionalProperties' => false,
                ),
            ),
        ),
        'ListSubscriptionsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Subscriptions' => array(
                    'description' => 'A list of subscriptions.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Subscription',
                        'description' => 'A wrapper type for the attributes of an SNS subscription.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'SubscriptionArn' => array(
                                'description' => 'The subscription\'s ARN.',
                                'type' => 'string',
                            ),
                            'Owner' => array(
                                'description' => 'The subscription\'s owner.',
                                'type' => 'string',
                            ),
                            'Protocol' => array(
                                'description' => 'The subscription\'s protocol.',
                                'type' => 'string',
                            ),
                            'Endpoint' => array(
                                'description' => 'The subscription\'s endpoint (format depends on the protocol).',
                                'type' => 'string',
                            ),
                            'TopicArn' => array(
                                'description' => 'The ARN of the subscription\'s topic.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'Token to pass along to the next ListSubscriptions request. This element is returned if there are more subscriptions to retrieve.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListSubscriptionsByTopicResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Subscriptions' => array(
                    'description' => 'A list of subscriptions.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Subscription',
                        'description' => 'A wrapper type for the attributes of an SNS subscription.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'SubscriptionArn' => array(
                                'description' => 'The subscription\'s ARN.',
                                'type' => 'string',
                            ),
                            'Owner' => array(
                                'description' => 'The subscription\'s owner.',
                                'type' => 'string',
                            ),
                            'Protocol' => array(
                                'description' => 'The subscription\'s protocol.',
                                'type' => 'string',
                            ),
                            'Endpoint' => array(
                                'description' => 'The subscription\'s endpoint (format depends on the protocol).',
                                'type' => 'string',
                            ),
                            'TopicArn' => array(
                                'description' => 'The ARN of the subscription\'s topic.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'Token to pass along to the next ListSubscriptionsByTopic request. This element is returned if there are more subscriptions to retrieve.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListTopicsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Topics' => array(
                    'description' => 'A list of topic ARNs.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Topic',
                        'description' => 'A wrapper type for the topic\'s Amazon Resource Name (ARN). To retrieve a topic\'s attributes, use GetTopicAttributes.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'TopicArn' => array(
                                'description' => 'The topic\'s ARN.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'Token to pass along to the next ListTopics request. This element is returned if there are additional topics to retrieve.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'PublishResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'MessageId' => array(
                    'description' => 'Unique identifier assigned to the published message.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'SubscribeResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SubscriptionArn' => array(
                    'description' => 'The ARN of the subscription, if the service was able to create a subscription immediately (without requiring endpoint owner confirmation).',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'ListSubscriptions' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'result_key' => 'Subscriptions',
            ),
            'ListSubscriptionsByTopic' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'result_key' => 'Subscriptions',
            ),
            'ListTopics' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'result_key' => 'Topics/*/TopicArn',
            ),
        ),
    ),
);
