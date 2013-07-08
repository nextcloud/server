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
    'apiVersion' => '2010-05-15',
    'endpointPrefix' => 'cloudformation',
    'serviceFullName' => 'AWS CloudFormation',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'CloudFormation',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudformation.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudformation.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudformation.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudformation.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudformation.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudformation.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudformation.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'cloudformation.sa-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CancelUpdateStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Cancels an update on the specified stack. If the call completes successfully, the stack will roll back the update and revert to the previous stack configuration.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CancelUpdateStack',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'required' => true,
                    'description' => 'The name or the unique identifier associated with the stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateStackOutput',
            'responseType' => 'model',
            'summary' => 'Creates a stack as specified in the template. After the call completes successfully, the stack creation starts. You can check the status of the stack via the DescribeStacks API.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateStack',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'required' => true,
                    'description' => 'The name associated with the stack. The name must be unique within your AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'TemplateBody' => array(
                    'description' => 'Structure containing the template body. (For more information, go to the AWS CloudFormation User Guide.)',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 51200,
                ),
                'TemplateURL' => array(
                    'description' => 'Location of file containing the template body. The URL must point to a template (max size: 307,200 bytes) located in an S3 bucket in the same region as the stack. For more information, go to the AWS CloudFormation User Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'Parameters' => array(
                    'description' => 'A list of Parameter structures that specify input parameters for the stack.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Parameters.member',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'The Parameter data type.',
                        'type' => 'object',
                        'properties' => array(
                            'ParameterKey' => array(
                                'description' => 'The key associated with the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'The value associated with the parameter.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'DisableRollback' => array(
                    'description' => 'Set to true to disable rollback of the stack if stack creation failed. You can specify either DisableRollback or OnFailure, but not both.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'TimeoutInMinutes' => array(
                    'description' => 'The amount of time that can pass before the stack status becomes CREATE_FAILED; if DisableRollback is not set or is set to false, the stack will be rolled back.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                ),
                'NotificationARNs' => array(
                    'description' => 'The Simple Notification Service (SNS) topic ARNs to publish stack related events. You can find your SNS topic ARNs using the SNS console or your Command Line Interface (CLI).',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'NotificationARNs.member',
                    'maxItems' => 5,
                    'items' => array(
                        'name' => 'NotificationARN',
                        'type' => 'string',
                    ),
                ),
                'Capabilities' => array(
                    'description' => 'The list of capabilities that you want to allow in the stack. If your template contains IAM resources, you must specify the CAPABILITY_IAM value for this parameter; otherwise, this action returns an InsufficientCapabilities error. IAM resources are the following: AWS::IAM::AccessKey, AWS::IAM::Group, AWS::IAM::Policy, AWS::IAM::User, and AWS::IAM::UserToGroupAddition.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Capabilities.member',
                    'items' => array(
                        'name' => 'Capability',
                        'type' => 'string',
                        'enum' => array(
                            'CAPABILITY_IAM',
                        ),
                    ),
                ),
                'OnFailure' => array(
                    'description' => 'Determines what action will be taken if stack creation fails. This must be one of: DO_NOTHING, ROLLBACK, or DELETE. You can specify either OnFailure or DisableRollback, but not both.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'DO_NOTHING',
                        'ROLLBACK',
                        'DELETE',
                    ),
                ),
                'Tags' => array(
                    'description' => 'A set of user-defined Tags to associate with this stack, represented by key/value pairs. Tags defined for the stack are propogated to EC2 resources that are created as part of the stack. A maximum number of 10 tags can be specified.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Tags.member',
                    'items' => array(
                        'name' => 'Tag',
                        'description' => 'The Tag type is used by CreateStack in the Tags parameter. It allows you to specify a key/value pair that can be used to store information related to cost allocation for an AWS CloudFormation stack.',
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'Required. A string used to identify this tag. You can specify a maximum of 128 characters for a tag key. Tags owned by Amazon Web Services (AWS) have the reserved prefix: aws:.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'Required. A string containing the value for this tag. You can specify a maximum of 256 characters for a tag value.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Quota for the resource has already been reached.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'Resource with the name requested already exists.',
                    'class' => 'AlreadyExistsException',
                ),
                array(
                    'reason' => 'The template contains resources with capabilities that were not specified in the Capabilities parameter.',
                    'class' => 'InsufficientCapabilitiesException',
                ),
            ),
        ),
        'DeleteStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a specified stack. Once the call completes successfully, stack deletion starts. Deleted stacks do not show up in the DescribeStacks API if the deletion has been completed successfully.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteStack',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'required' => true,
                    'description' => 'The name or the unique identifier associated with the stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeStackEvents' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeStackEventsOutput',
            'responseType' => 'model',
            'summary' => 'Returns all the stack related events for the AWS account. If StackName is specified, returns events related to all the stacks with the given name. If StackName is not specified, returns all the events for the account. For more information about a stack\'s event history, go to the AWS CloudFormation User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeStackEvents',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'description' => 'The name or the unique identifier associated with the stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'description' => 'String that identifies the start of the next list of events, if there is one.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
            ),
        ),
        'DescribeStackResource' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeStackResourceOutput',
            'responseType' => 'model',
            'summary' => 'Returns a description of the specified resource in the specified stack.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeStackResource',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'required' => true,
                    'description' => 'The name or the unique identifier associated with the stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LogicalResourceId' => array(
                    'required' => true,
                    'description' => 'The logical name of the resource as specified in the template.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeStackResources' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeStackResourcesOutput',
            'responseType' => 'model',
            'summary' => 'Returns AWS resource descriptions for running and deleted stacks. If StackName is specified, all the associated resources that are part of the stack are returned. If PhysicalResourceId is specified, the associated resources of the stack that the resource belongs to are returned.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeStackResources',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'description' => 'The name or the unique identifier associated with the stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LogicalResourceId' => array(
                    'description' => 'The logical name of the resource as specified in the template.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PhysicalResourceId' => array(
                    'description' => 'The name or unique identifier that corresponds to a physical instance ID of a resource supported by AWS CloudFormation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeStacks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeStacksOutput',
            'responseType' => 'model',
            'summary' => 'Returns the description for the specified stack; if no stack name was specified, then it returns the description for all the stacks created.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeStacks',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'description' => 'The name or the unique identifier associated with the stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
            ),
        ),
        'EstimateTemplateCost' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EstimateTemplateCostOutput',
            'responseType' => 'model',
            'summary' => 'Returns the estimated monthly cost of a template. The return value is an AWS Simple Monthly Calculator URL with a query string that describes the resources required to run the template.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'EstimateTemplateCost',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'TemplateBody' => array(
                    'description' => 'Structure containing the template body. (For more information, go to the AWS CloudFormation User Guide.)',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 51200,
                ),
                'TemplateURL' => array(
                    'description' => 'Location of file containing the template body. The URL must point to a template located in an S3 bucket in the same region as the stack. For more information, go to the AWS CloudFormation User Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'Parameters' => array(
                    'description' => 'A list of Parameter structures that specify input parameters.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Parameters.member',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'The Parameter data type.',
                        'type' => 'object',
                        'properties' => array(
                            'ParameterKey' => array(
                                'description' => 'The key associated with the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'The value associated with the parameter.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'GetTemplate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetTemplateOutput',
            'responseType' => 'model',
            'summary' => 'Returns the template body for a specified stack name. You can get the template for running or deleted stacks.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetTemplate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'required' => true,
                    'description' => 'The name or the unique identifier associated with the stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ListStackResources' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListStackResourcesOutput',
            'responseType' => 'model',
            'summary' => 'Returns descriptions of all resources of the specified stack.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListStackResources',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'required' => true,
                    'description' => 'The name or the unique identifier associated with the stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'description' => 'String that identifies the start of the next list of stack resource summaries, if there is one.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
            ),
        ),
        'ListStacks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListStacksOutput',
            'responseType' => 'model',
            'summary' => 'Returns the summary information for stacks whose status matches the specified StackStatusFilter. Summary information for stacks that have been deleted is kept for 90 days after the stack is deleted. If no StackStatusFilter is specified, summary information for all stacks is returned (including existing stacks and stacks that have been deleted).',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListStacks',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'NextToken' => array(
                    'description' => 'String that identifies the start of the next list of stacks, if there is one.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'StackStatusFilter' => array(
                    'description' => 'Stack status to use as a filter. Specify one or more stack status codes to list only stacks with the specified status codes. For a complete list of stack status codes, see the StackStatus parameter of the Stack data type.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'StackStatusFilter.member',
                    'items' => array(
                        'name' => 'StackStatus',
                        'type' => 'string',
                        'enum' => array(
                            'CREATE_IN_PROGRESS',
                            'CREATE_FAILED',
                            'CREATE_COMPLETE',
                            'ROLLBACK_IN_PROGRESS',
                            'ROLLBACK_FAILED',
                            'ROLLBACK_COMPLETE',
                            'DELETE_IN_PROGRESS',
                            'DELETE_FAILED',
                            'DELETE_COMPLETE',
                            'UPDATE_IN_PROGRESS',
                            'UPDATE_COMPLETE_CLEANUP_IN_PROGRESS',
                            'UPDATE_COMPLETE',
                            'UPDATE_ROLLBACK_IN_PROGRESS',
                            'UPDATE_ROLLBACK_FAILED',
                            'UPDATE_ROLLBACK_COMPLETE_CLEANUP_IN_PROGRESS',
                            'UPDATE_ROLLBACK_COMPLETE',
                        ),
                    ),
                ),
            ),
        ),
        'UpdateStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UpdateStackOutput',
            'responseType' => 'model',
            'summary' => 'Updates a stack as specified in the template. After the call completes successfully, the stack update starts. You can check the status of the stack via the DescribeStacks action.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateStack',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'StackName' => array(
                    'required' => true,
                    'description' => 'The name or stack ID of the stack to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'TemplateBody' => array(
                    'description' => 'Structure containing the template body. (For more information, go to the AWS CloudFormation User Guide.)',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 51200,
                ),
                'TemplateURL' => array(
                    'description' => 'Location of file containing the template body. The URL must point to a template located in an S3 bucket in the same region as the stack. For more information, go to the AWS CloudFormation User Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'Parameters' => array(
                    'description' => 'A list of Parameter structures that specify input parameters for the stack.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Parameters.member',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'The Parameter data type.',
                        'type' => 'object',
                        'properties' => array(
                            'ParameterKey' => array(
                                'description' => 'The key associated with the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'The value associated with the parameter.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Capabilities' => array(
                    'description' => 'The list of capabilities that you want to allow in the stack. If your stack contains IAM resources, you must specify the CAPABILITY_IAM value for this parameter; otherwise, this action returns an InsufficientCapabilities error. IAM resources are the following: AWS::IAM::AccessKey, AWS::IAM::Group, AWS::IAM::Policy, AWS::IAM::User, and AWS::IAM::UserToGroupAddition.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Capabilities.member',
                    'items' => array(
                        'name' => 'Capability',
                        'type' => 'string',
                        'enum' => array(
                            'CAPABILITY_IAM',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The template contains resources with capabilities that were not specified in the Capabilities parameter.',
                    'class' => 'InsufficientCapabilitiesException',
                ),
            ),
        ),
        'ValidateTemplate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ValidateTemplateOutput',
            'responseType' => 'model',
            'summary' => 'Validates a specified template.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ValidateTemplate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-05-15',
                ),
                'TemplateBody' => array(
                    'description' => 'String containing the template body. (For more information, go to the AWS CloudFormation User Guide.)',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 51200,
                ),
                'TemplateURL' => array(
                    'description' => 'Location of file containing the template body. The URL must point to a template (max size: 307,200 bytes) located in an S3 bucket in the same region as the stack. For more information, go to the AWS CloudFormation User Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'CreateStackOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackId' => array(
                    'description' => 'Unique identifier of the stack.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DescribeStackEventsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackEvents' => array(
                    'description' => 'A list of StackEvents structures.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'StackEvent',
                        'description' => 'The StackEvent data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'StackId' => array(
                                'description' => 'The unique ID name of the instance of the stack.',
                                'type' => 'string',
                            ),
                            'EventId' => array(
                                'description' => 'The unique ID of this event.',
                                'type' => 'string',
                            ),
                            'StackName' => array(
                                'description' => 'The name associated with a stack.',
                                'type' => 'string',
                            ),
                            'LogicalResourceId' => array(
                                'description' => 'The logical name of the resource specified in the template.',
                                'type' => 'string',
                            ),
                            'PhysicalResourceId' => array(
                                'description' => 'The name or unique identifier associated with the physical instance of the resource.',
                                'type' => 'string',
                            ),
                            'ResourceType' => array(
                                'description' => 'Type of the resource. (For more information, go to the AWS CloudFormation User Guide.)',
                                'type' => 'string',
                            ),
                            'Timestamp' => array(
                                'description' => 'Time the status was updated.',
                                'type' => 'string',
                            ),
                            'ResourceStatus' => array(
                                'description' => 'Current status of the resource.',
                                'type' => 'string',
                            ),
                            'ResourceStatusReason' => array(
                                'description' => 'Success/failure message associated with the resource.',
                                'type' => 'string',
                            ),
                            'ResourceProperties' => array(
                                'description' => 'BLOB of the properties used to create the resource.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'String that identifies the start of the next list of events, if there is one.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DescribeStackResourceOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackResourceDetail' => array(
                    'description' => 'A StackResourceDetail structure containing the description of the specified resource in the specified stack.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'StackName' => array(
                            'description' => 'The name associated with the stack.',
                            'type' => 'string',
                        ),
                        'StackId' => array(
                            'description' => 'Unique identifier of the stack.',
                            'type' => 'string',
                        ),
                        'LogicalResourceId' => array(
                            'description' => 'The logical name of the resource specified in the template.',
                            'type' => 'string',
                        ),
                        'PhysicalResourceId' => array(
                            'description' => 'The name or unique identifier that corresponds to a physical instance ID of a resource supported by AWS CloudFormation.',
                            'type' => 'string',
                        ),
                        'ResourceType' => array(
                            'description' => 'Type of the resource. (For more information, go to the AWS CloudFormation User Guide.)',
                            'type' => 'string',
                        ),
                        'LastUpdatedTimestamp' => array(
                            'description' => 'Time the status was updated.',
                            'type' => 'string',
                        ),
                        'ResourceStatus' => array(
                            'description' => 'Current status of the resource.',
                            'type' => 'string',
                        ),
                        'ResourceStatusReason' => array(
                            'description' => 'Success/failure message associated with the resource.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'User defined description associated with the resource.',
                            'type' => 'string',
                        ),
                        'Metadata' => array(
                            'description' => 'The JSON format content of the Metadata attribute declared for the resource. For more information, see Metadata Attribute in the AWS CloudFormation User Guide.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'DescribeStackResourcesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackResources' => array(
                    'description' => 'A list of StackResource structures.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'StackResource',
                        'description' => 'The StackResource data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'StackName' => array(
                                'description' => 'The name associated with the stack.',
                                'type' => 'string',
                            ),
                            'StackId' => array(
                                'description' => 'Unique identifier of the stack.',
                                'type' => 'string',
                            ),
                            'LogicalResourceId' => array(
                                'description' => 'The logical name of the resource specified in the template.',
                                'type' => 'string',
                            ),
                            'PhysicalResourceId' => array(
                                'description' => 'The name or unique identifier that corresponds to a physical instance ID of a resource supported by AWS CloudFormation.',
                                'type' => 'string',
                            ),
                            'ResourceType' => array(
                                'description' => 'Type of the resource. (For more information, go to the AWS CloudFormation User Guide.)',
                                'type' => 'string',
                            ),
                            'Timestamp' => array(
                                'description' => 'Time the status was updated.',
                                'type' => 'string',
                            ),
                            'ResourceStatus' => array(
                                'description' => 'Current status of the resource.',
                                'type' => 'string',
                            ),
                            'ResourceStatusReason' => array(
                                'description' => 'Success/failure message associated with the resource.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'User defined description associated with the resource.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeStacksOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Stacks' => array(
                    'description' => 'A list of stack structures.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Stack',
                        'description' => 'The Stack data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'StackId' => array(
                                'description' => 'Unique identifier of the stack.',
                                'type' => 'string',
                            ),
                            'StackName' => array(
                                'description' => 'The name associated with the stack.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'User defined description associated with the stack.',
                                'type' => 'string',
                            ),
                            'Parameters' => array(
                                'description' => 'A list of Parameter structures.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Parameter',
                                    'description' => 'The Parameter data type.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'ParameterKey' => array(
                                            'description' => 'The key associated with the parameter.',
                                            'type' => 'string',
                                        ),
                                        'ParameterValue' => array(
                                            'description' => 'The value associated with the parameter.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'CreationTime' => array(
                                'description' => 'Time at which the stack was created.',
                                'type' => 'string',
                            ),
                            'LastUpdatedTime' => array(
                                'description' => 'The time the stack was last updated. This field will only be returned if the stack has been updated at least once.',
                                'type' => 'string',
                            ),
                            'StackStatus' => array(
                                'description' => 'Current status of the stack.',
                                'type' => 'string',
                            ),
                            'StackStatusReason' => array(
                                'description' => 'Success/failure message associated with the stack status.',
                                'type' => 'string',
                            ),
                            'DisableRollback' => array(
                                'description' => 'Boolean to enable or disable rollback on stack creation failures:',
                                'type' => 'boolean',
                            ),
                            'NotificationARNs' => array(
                                'description' => 'SNS topic ARNs to which stack related events are published.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'NotificationARN',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'TimeoutInMinutes' => array(
                                'description' => 'The amount of time within which stack creation should complete.',
                                'type' => 'numeric',
                            ),
                            'Capabilities' => array(
                                'description' => 'The capabilities allowed in the stack.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Capability',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'Outputs' => array(
                                'description' => 'A list of output structures.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Output',
                                    'description' => 'The Output data type.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'OutputKey' => array(
                                            'description' => 'The key associated with the output.',
                                            'type' => 'string',
                                        ),
                                        'OutputValue' => array(
                                            'description' => 'The value associated with the output.',
                                            'type' => 'string',
                                        ),
                                        'Description' => array(
                                            'description' => 'User defined description associated with the output.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'description' => 'A list of Tags that specify cost allocation information for the stack.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Tag',
                                    'description' => 'The Tag type is used by CreateStack in the Tags parameter. It allows you to specify a key/value pair that can be used to store information related to cost allocation for an AWS CloudFormation stack.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'Required. A string used to identify this tag. You can specify a maximum of 128 characters for a tag key. Tags owned by Amazon Web Services (AWS) have the reserved prefix: aws:.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'Required. A string containing the value for this tag. You can specify a maximum of 256 characters for a tag value.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'EstimateTemplateCostOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Url' => array(
                    'description' => 'An AWS Simple Monthly Calculator URL with a query string that describes the resources required to run the template.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'GetTemplateOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TemplateBody' => array(
                    'description' => 'Structure containing the template body. (For more information, go to the AWS CloudFormation User Guide.)',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListStackResourcesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackResourceSummaries' => array(
                    'description' => 'A list of StackResourceSummary structures.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'StackResourceSummary',
                        'description' => 'Contains high-level information about the specified stack resource.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'LogicalResourceId' => array(
                                'description' => 'The logical name of the resource specified in the template.',
                                'type' => 'string',
                            ),
                            'PhysicalResourceId' => array(
                                'description' => 'The name or unique identifier that corresponds to a physical instance ID of the resource.',
                                'type' => 'string',
                            ),
                            'ResourceType' => array(
                                'description' => 'Type of the resource. (For more information, go to the AWS CloudFormation User Guide.)',
                                'type' => 'string',
                            ),
                            'LastUpdatedTimestamp' => array(
                                'description' => 'Time the status was updated.',
                                'type' => 'string',
                            ),
                            'ResourceStatus' => array(
                                'description' => 'Current status of the resource.',
                                'type' => 'string',
                            ),
                            'ResourceStatusReason' => array(
                                'description' => 'Success/failure message associated with the resource.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'String that identifies the start of the next list of events, if there is one.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListStacksOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackSummaries' => array(
                    'description' => 'A list of StackSummary structures containing information about the specified stacks.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'StackSummary',
                        'description' => 'The StackSummary Data Type',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'StackId' => array(
                                'description' => 'Unique stack identifier.',
                                'type' => 'string',
                            ),
                            'StackName' => array(
                                'description' => 'The name associated with the stack.',
                                'type' => 'string',
                            ),
                            'TemplateDescription' => array(
                                'description' => 'The template description of the template used to create the stack.',
                                'type' => 'string',
                            ),
                            'CreationTime' => array(
                                'description' => 'The time the stack was created.',
                                'type' => 'string',
                            ),
                            'LastUpdatedTime' => array(
                                'description' => 'The time the stack was last updated. This field will only be returned if the stack has been updated at least once.',
                                'type' => 'string',
                            ),
                            'DeletionTime' => array(
                                'description' => 'The time the stack was deleted.',
                                'type' => 'string',
                            ),
                            'StackStatus' => array(
                                'description' => 'The current status of the stack.',
                                'type' => 'string',
                            ),
                            'StackStatusReason' => array(
                                'description' => 'Success/Failure message associated with the stack status.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'String that identifies the start of the next list of stacks, if there is one.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'UpdateStackOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackId' => array(
                    'description' => 'Unique identifier of the stack.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ValidateTemplateOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Parameters' => array(
                    'description' => 'A list of TemplateParameter structures.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'TemplateParameter',
                        'description' => 'The TemplateParameter data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'ParameterKey' => array(
                                'description' => 'The name associated with the parameter.',
                                'type' => 'string',
                            ),
                            'DefaultValue' => array(
                                'description' => 'The default value associated with the parameter.',
                                'type' => 'string',
                            ),
                            'NoEcho' => array(
                                'description' => 'Flag indicating whether the parameter should be displayed as plain text in logs and UIs.',
                                'type' => 'boolean',
                            ),
                            'Description' => array(
                                'description' => 'User defined description associated with the parameter.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Description' => array(
                    'description' => 'The description found within the template.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Capabilities' => array(
                    'description' => 'The capabitilites found within the template. Currently, CAPABILITY_IAM is the only capability detected. If your template contains IAM resources, you must specify the CAPABILITY_IAM value for this parameter when you use the CreateStack or UpdateStack actions with your template; otherwise, those actions return an InsufficientCapabilities error.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Capability',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'CapabilitiesReason' => array(
                    'description' => 'The capabilities reason found within the template.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeStackEvents' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'result_key' => 'StackEvents',
            ),
            'DescribeStacks' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'result_key' => 'Stacks',
            ),
            'ListStackResources' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'result_key' => 'StackResourceSummaries',
            ),
            'ListStacks' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'result_key' => 'StackSummaries',
            ),
        ),
    ),
);
