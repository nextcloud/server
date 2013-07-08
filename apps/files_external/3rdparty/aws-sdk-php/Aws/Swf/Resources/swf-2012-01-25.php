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
    'apiVersion' => '2012-01-25',
    'endpointPrefix' => 'swf',
    'serviceFullName' => 'Amazon Simple Workflow Service',
    'serviceAbbreviation' => 'Amazon SWF',
    'serviceType' => 'json',
    'jsonVersion' => '1.0',
    'targetPrefix' => 'SimpleWorkflowService.',
    'timestampFormat' => 'unixTimestamp',
    'signatureVersion' => 'v3',
    'namespace' => 'Swf',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'swf.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CountClosedWorkflowExecutions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'WorkflowExecutionCount',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the number of closed workflow executions within the given domain that meet the specified filtering criteria.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.CountClosedWorkflowExecutions',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain containing the workflow executions to count.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'startTimeFilter' => array(
                    'description' => 'If specified, only workflow executions that meet the start time criteria of the filter are counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'oldestDate' => array(
                            'required' => true,
                            'description' => 'Specifies the oldest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                        'latestDate' => array(
                            'description' => 'Specifies the latest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                    ),
                ),
                'closeTimeFilter' => array(
                    'description' => 'If specified, only workflow executions that meet the close time criteria of the filter are counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'oldestDate' => array(
                            'required' => true,
                            'description' => 'Specifies the oldest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                        'latestDate' => array(
                            'description' => 'Specifies the latest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                    ),
                ),
                'executionFilter' => array(
                    'description' => 'If specified, only workflow executions matching the WorkflowId in the filter are counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowId' => array(
                            'required' => true,
                            'description' => 'The workflowId to pass of match the criteria of this filter.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'typeFilter' => array(
                    'description' => 'If specified, indicates the type of the workflow executions to be counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'Name of the workflow type. This field is required.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'description' => 'Version of the workflow type.',
                            'type' => 'string',
                            'maxLength' => 64,
                        ),
                    ),
                ),
                'tagFilter' => array(
                    'description' => 'If specified, only executions that have a tag that matches the filter are counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'tag' => array(
                            'required' => true,
                            'description' => 'Specifies the tag that must be associated with the execution for it to meet the filter criteria. This field is required.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'closeStatusFilter' => array(
                    'description' => 'If specified, only workflow executions that match this close status are counted. This filter has an affect only if executionStatus is specified as CLOSED.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'status' => array(
                            'required' => true,
                            'description' => 'The close status that must match the close status of an execution for it to meet the criteria of this filter. This field is required.',
                            'type' => 'string',
                            'enum' => array(
                                'COMPLETED',
                                'FAILED',
                                'CANCELED',
                                'TERMINATED',
                                'CONTINUED_AS_NEW',
                                'TIMED_OUT',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'CountOpenWorkflowExecutions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'WorkflowExecutionCount',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the number of open workflow executions within the given domain that meet the specified filtering criteria.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.CountOpenWorkflowExecutions',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain containing the workflow executions to count.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'startTimeFilter' => array(
                    'required' => true,
                    'description' => 'Specifies the start time criteria that workflow executions must meet in order to be counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'oldestDate' => array(
                            'required' => true,
                            'description' => 'Specifies the oldest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                        'latestDate' => array(
                            'description' => 'Specifies the latest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                    ),
                ),
                'typeFilter' => array(
                    'description' => 'Specifies the type of the workflow executions to be counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'Name of the workflow type. This field is required.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'description' => 'Version of the workflow type.',
                            'type' => 'string',
                            'maxLength' => 64,
                        ),
                    ),
                ),
                'tagFilter' => array(
                    'description' => 'If specified, only executions that have a tag that matches the filter are counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'tag' => array(
                            'required' => true,
                            'description' => 'Specifies the tag that must be associated with the execution for it to meet the filter criteria. This field is required.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'executionFilter' => array(
                    'description' => 'If specified, only workflow executions matching the WorkflowId in the filter are counted.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowId' => array(
                            'required' => true,
                            'description' => 'The workflowId to pass of match the criteria of this filter.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'CountPendingActivityTasks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'PendingTaskCount',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the estimated number of activity tasks in the specified task list. The count returned is an approximation and is not guaranteed to be exact. If you specify a task list that no activity task was ever scheduled in then 0 will be returned.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.CountPendingActivityTasks',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain that contains the task list.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'taskList' => array(
                    'required' => true,
                    'description' => 'The name of the task list.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the task list.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'CountPendingDecisionTasks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'PendingTaskCount',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the estimated number of decision tasks in the specified task list. The count returned is an approximation and is not guaranteed to be exact. If you specify a task list that no decision task was ever scheduled in then 0 will be returned.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.CountPendingDecisionTasks',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain that contains the task list.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'taskList' => array(
                    'required' => true,
                    'description' => 'The name of the task list.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the task list.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'DeprecateActivityType' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deprecates the specified activity type. After an activity type has been deprecated, you cannot create new tasks of that activity type. Tasks of this type that were scheduled before the type was deprecated will continue to run.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.DeprecateActivityType',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which the activity type is registered.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'activityType' => array(
                    'required' => true,
                    'description' => 'The activity type to deprecate.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'required' => true,
                            'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the specified activity or workflow type was already deprecated.',
                    'class' => 'TypeDeprecatedException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'DeprecateDomain' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deprecates the specified domain. After a domain has been deprecated it cannot be used to create new workflow executions or register new types. However, you can still use visibility actions on this domain. Deprecating a domain also deprecates all activity and workflow types registered in the domain. Executions that were started before the domain was deprecated will continue to run.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.DeprecateDomain',
                ),
                'name' => array(
                    'required' => true,
                    'description' => 'The name of the domain to deprecate.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the specified domain has been deprecated.',
                    'class' => 'DomainDeprecatedException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'DeprecateWorkflowType' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deprecates the specified workflow type. After a workflow type has been deprecated, you cannot create new executions of that type. Executions that were started before the type was deprecated will continue to run. A deprecated workflow type may still be used when calling visibility actions.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.DeprecateWorkflowType',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which the workflow type is registered.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'workflowType' => array(
                    'required' => true,
                    'description' => 'The workflow type to deprecate.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'required' => true,
                            'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the specified activity or workflow type was already deprecated.',
                    'class' => 'TypeDeprecatedException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'DescribeActivityType' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ActivityTypeDetail',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns information about the specified activity type. This includes configuration settings provided at registration time as well as other general information about the type.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.DescribeActivityType',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which the activity type is registered.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'activityType' => array(
                    'required' => true,
                    'description' => 'The activity type to describe.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'required' => true,
                            'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'DescribeDomain' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DomainDetail',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns information about the specified domain including description and status.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.DescribeDomain',
                ),
                'name' => array(
                    'required' => true,
                    'description' => 'The name of the domain to describe.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'DescribeWorkflowExecution' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'WorkflowExecutionDetail',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns information about the specified workflow execution including its type and some statistics.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.DescribeWorkflowExecution',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain containing the workflow execution.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'execution' => array(
                    'required' => true,
                    'description' => 'The workflow execution to describe.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowId' => array(
                            'required' => true,
                            'description' => 'The user defined identifier associated with the workflow execution.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'runId' => array(
                            'required' => true,
                            'description' => 'A system generated unique identifier for the workflow execution.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'DescribeWorkflowType' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'WorkflowTypeDetail',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns information about the specified workflow type. This includes configuration settings specified when the type was registered and other information such as creation date, current status, etc.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.DescribeWorkflowType',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which this workflow type is registered.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'workflowType' => array(
                    'required' => true,
                    'description' => 'The workflow type to describe.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'required' => true,
                            'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'GetWorkflowExecutionHistory' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'History',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the history of the specified workflow execution. The results may be split into multiple pages. To retrieve subsequent pages, make the call again using the nextPageToken returned by the initial call.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.GetWorkflowExecutionHistory',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain containing the workflow execution.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'execution' => array(
                    'required' => true,
                    'description' => 'Specifies the workflow execution for which to return the history.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowId' => array(
                            'required' => true,
                            'description' => 'The user defined identifier associated with the workflow execution.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'runId' => array(
                            'required' => true,
                            'description' => 'A system generated unique identifier for the workflow execution.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'If a NextPageToken is returned, the result has more than one pages. To get the next page, repeat the call and specify the nextPageToken with all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 2048,
                ),
                'maximumPageSize' => array(
                    'description' => 'Specifies the maximum number of history events returned in one page. The next page in the result is identified by the NextPageToken returned. By default 100 history events are returned in a page but the caller can override this value to a page size smaller than the default. You cannot specify a page size larger than 100. Note that the number of events may be less than the maxiumum page size, in which case, the returned page will have fewer results than the maximumPageSize specified.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 1000,
                ),
                'reverseOrder' => array(
                    'description' => 'When set to true, returns the events in reverse order. By default the results are returned in ascending order of the eventTimeStamp of the events.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'ListActivityTypes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ActivityTypeInfos',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns information about all activities registered in the specified domain that match the specified name and registration status. The result includes information like creation date, current status of the activity, etc. The results may be split into multiple pages. To retrieve subsequent pages, make the call again using the nextPageToken returned by the initial call.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.ListActivityTypes',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which the activity types have been registered.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'name' => array(
                    'description' => 'If specified, only lists the activity types that have this name.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'registrationStatus' => array(
                    'required' => true,
                    'description' => 'Specifies the registration status of the activity types to list.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'REGISTERED',
                        'DEPRECATED',
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'If on a previous call to this method a NextResultToken was returned, the results have more than one page. To get the next page of results, repeat the call with the nextPageToken and keep all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 2048,
                ),
                'maximumPageSize' => array(
                    'description' => 'The maximum number of results returned in each page. The default is 100, but the caller can override this value to a page size smaller than the default. You cannot specify a page size greater than 100. Note that the number of types may be less than the maxiumum page size, in which case, the returned page will have fewer results than the maximumPageSize specified.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 1000,
                ),
                'reverseOrder' => array(
                    'description' => 'When set to true, returns the results in reverse order. By default the results are returned in ascending alphabetical order of the name of the activity types.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
            ),
        ),
        'ListClosedWorkflowExecutions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'WorkflowExecutionInfos',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns a list of closed workflow executions in the specified domain that meet the filtering criteria. The results may be split into multiple pages. To retrieve subsequent pages, make the call again using the nextPageToken returned by the initial call.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.ListClosedWorkflowExecutions',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain that contains the workflow executions to list.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'startTimeFilter' => array(
                    'description' => 'If specified, the workflow executions are included in the returned results based on whether their start times are within the range specified by this filter. Also, if this parameter is specified, the returned results are ordered by their start times.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'oldestDate' => array(
                            'required' => true,
                            'description' => 'Specifies the oldest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                        'latestDate' => array(
                            'description' => 'Specifies the latest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                    ),
                ),
                'closeTimeFilter' => array(
                    'description' => 'If specified, the workflow executions are included in the returned results based on whether their close times are within the range specified by this filter. Also, if this parameter is specified, the returned results are ordered by their close times.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'oldestDate' => array(
                            'required' => true,
                            'description' => 'Specifies the oldest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                        'latestDate' => array(
                            'description' => 'Specifies the latest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                    ),
                ),
                'executionFilter' => array(
                    'description' => 'If specified, only workflow executions matching the workflow id specified in the filter are returned.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowId' => array(
                            'required' => true,
                            'description' => 'The workflowId to pass of match the criteria of this filter.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'closeStatusFilter' => array(
                    'description' => 'If specified, only workflow executions that match this close status are listed. For example, if TERMINATED is specified, then only TERMINATED workflow executions are listed.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'status' => array(
                            'required' => true,
                            'description' => 'The close status that must match the close status of an execution for it to meet the criteria of this filter. This field is required.',
                            'type' => 'string',
                            'enum' => array(
                                'COMPLETED',
                                'FAILED',
                                'CANCELED',
                                'TERMINATED',
                                'CONTINUED_AS_NEW',
                                'TIMED_OUT',
                            ),
                        ),
                    ),
                ),
                'typeFilter' => array(
                    'description' => 'If specified, only executions of the type specified in the filter are returned.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'Name of the workflow type. This field is required.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'description' => 'Version of the workflow type.',
                            'type' => 'string',
                            'maxLength' => 64,
                        ),
                    ),
                ),
                'tagFilter' => array(
                    'description' => 'If specified, only executions that have the matching tag are listed.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'tag' => array(
                            'required' => true,
                            'description' => 'Specifies the tag that must be associated with the execution for it to meet the filter criteria. This field is required.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'If on a previous call to this method a NextPageToken was returned, the results are being paginated. To get the next page of results, repeat the call with the returned token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 2048,
                ),
                'maximumPageSize' => array(
                    'description' => 'The maximum number of results returned in each page. The default is 100, but the caller can override this value to a page size smaller than the default. You cannot specify a page size greater than 100. Note that the number of executions may be less than the maxiumum page size, in which case, the returned page will have fewer results than the maximumPageSize specified.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 1000,
                ),
                'reverseOrder' => array(
                    'description' => 'When set to true, returns the results in reverse order. By default the results are returned in descending order of the start or the close time of the executions.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'ListDomains' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DomainInfos',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the list of domains registered in the account. The results may be split into multiple pages. To retrieve subsequent pages, make the call again using the nextPageToken returned by the initial call.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.ListDomains',
                ),
                'nextPageToken' => array(
                    'description' => 'If on a previous call to this method a NextPageToken was returned, the result has more than one page. To get the next page of results, repeat the call with the returned token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 2048,
                ),
                'registrationStatus' => array(
                    'required' => true,
                    'description' => 'Specifies the registration status of the domains to list.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'REGISTERED',
                        'DEPRECATED',
                    ),
                ),
                'maximumPageSize' => array(
                    'description' => 'The maximum number of results returned in each page. The default is 100, but the caller can override this value to a page size smaller than the default. You cannot specify a page size greater than 100. Note that the number of domains may be less than the maxiumum page size, in which case, the returned page will have fewer results than the maximumPageSize specified.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 1000,
                ),
                'reverseOrder' => array(
                    'description' => 'When set to true, returns the results in reverse order. By default the results are returned in ascending alphabetical order of the name of the domains.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'ListOpenWorkflowExecutions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'WorkflowExecutionInfos',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns a list of open workflow executions in the specified domain that meet the filtering criteria. The results may be split into multiple pages. To retrieve subsequent pages, make the call again using the nextPageToken returned by the initial call.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.ListOpenWorkflowExecutions',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain that contains the workflow executions to list.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'startTimeFilter' => array(
                    'required' => true,
                    'description' => 'Workflow executions are included in the returned results based on whether their start times are within the range specified by this filter.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'oldestDate' => array(
                            'required' => true,
                            'description' => 'Specifies the oldest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                        'latestDate' => array(
                            'description' => 'Specifies the latest start or close date and time to return.',
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                        ),
                    ),
                ),
                'typeFilter' => array(
                    'description' => 'If specified, only executions of the type specified in the filter are returned.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'Name of the workflow type. This field is required.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'description' => 'Version of the workflow type.',
                            'type' => 'string',
                            'maxLength' => 64,
                        ),
                    ),
                ),
                'tagFilter' => array(
                    'description' => 'If specified, only executions that have the matching tag are listed.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'tag' => array(
                            'required' => true,
                            'description' => 'Specifies the tag that must be associated with the execution for it to meet the filter criteria. This field is required.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'If on a previous call to this method a NextPageToken was returned, the results are being paginated. To get the next page of results, repeat the call with the returned token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 2048,
                ),
                'maximumPageSize' => array(
                    'description' => 'The maximum number of results returned in each page. The default is 100, but the caller can override this value to a page size smaller than the default. You cannot specify a page size greater than 100. Note that the number of executions may be less than the maxiumum page size, in which case, the returned page will have fewer results than the maximumPageSize specified.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 1000,
                ),
                'reverseOrder' => array(
                    'description' => 'When set to true, returns the results in reverse order. By default the results are returned in descending order of the start time of the executions.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'executionFilter' => array(
                    'description' => 'If specified, only workflow executions matching the workflow id specified in the filter are returned.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowId' => array(
                            'required' => true,
                            'description' => 'The workflowId to pass of match the criteria of this filter.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'ListWorkflowTypes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'WorkflowTypeInfos',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns information about workflow types in the specified domain. The results may be split into multiple pages that can be retrieved by making the call repeatedly.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.ListWorkflowTypes',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which the workflow types have been registered.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'name' => array(
                    'description' => 'If specified, lists the workflow type with this name.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'registrationStatus' => array(
                    'required' => true,
                    'description' => 'Specifies the registration status of the workflow types to list.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'REGISTERED',
                        'DEPRECATED',
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'If on a previous call to this method a NextPageToken was returned, the results are being paginated. To get the next page of results, repeat the call with the returned token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 2048,
                ),
                'maximumPageSize' => array(
                    'description' => 'The maximum number of results returned in each page. The default is 100, but the caller can override this value to a page size smaller than the default. You cannot specify a page size greater than 100. Note that the number of types may be less than the maxiumum page size, in which case, the returned page will have fewer results than the maximumPageSize specified.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 1000,
                ),
                'reverseOrder' => array(
                    'description' => 'When set to true, returns the results in reverse order. By default the results are returned in ascending alphabetical order of the name of the workflow types.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
            ),
        ),
        'PollForActivityTask' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ActivityTask',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Used by workers to get an ActivityTask from the specified activity taskList. This initiates a long poll, where the service holds the HTTP connection open and responds as soon as a task becomes available. The maximum time the service holds on to the request before responding is 60 seconds. If no task is available within 60 seconds, the poll will return an empty result. An empty result, in this context, means that an ActivityTask is returned, but that the value of taskToken is an empty string. If a task is returned, the worker should use its type to identify and process it correctly.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.PollForActivityTask',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain that contains the task lists being polled.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'taskList' => array(
                    'required' => true,
                    'description' => 'Specifies the task list to poll for activity tasks.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the task list.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'identity' => array(
                    'description' => 'Identity of the worker making the request, which is recorded in the ActivityTaskStarted event in the workflow history. This enables diagnostic tracing when problems arise. The form of this identity is user defined.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 256,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
                array(
                    'reason' => 'Returned by any operation if a system imposed limitation has been reached. To address this fault you should either clean up unused resources or increase the limit by contacting AWS.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'PollForDecisionTask' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DecisionTask',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Used by deciders to get a DecisionTask from the specified decision taskList. A decision task may be returned for any open workflow execution that is using the specified task list. The task includes a paginated view of the history of the workflow execution. The decider should use the workflow type and the history to determine how to properly handle the task.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.PollForDecisionTask',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain containing the task lists to poll.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'taskList' => array(
                    'required' => true,
                    'description' => 'Specifies the task list to poll for decision tasks.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the task list.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'identity' => array(
                    'description' => 'Identity of the decider making the request, which is recorded in the DecisionTaskStarted event in the workflow history. This enables diagnostic tracing when problems arise. The form of this identity is user defined.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 256,
                ),
                'nextPageToken' => array(
                    'description' => 'If on a previous call to this method a NextPageToken was returned, the results are being paginated. To get the next page of results, repeat the call with the returned token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 2048,
                ),
                'maximumPageSize' => array(
                    'description' => 'The maximum number of history events returned in each page. The default is 100, but the caller can override this value to a page size smaller than the default. You cannot specify a page size greater than 100. Note that the number of events may be less than the maxiumum page size, in which case, the returned page will have fewer results than the maximumPageSize specified.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 1000,
                ),
                'reverseOrder' => array(
                    'description' => 'When set to true, returns the events in reverse order. By default the results are returned in ascending order of the eventTimestamp of the events.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
                array(
                    'reason' => 'Returned by any operation if a system imposed limitation has been reached. To address this fault you should either clean up unused resources or increase the limit by contacting AWS.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'RecordActivityTaskHeartbeat' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ActivityTaskStatus',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Used by activity workers to report to the service that the ActivityTask represented by the specified taskToken is still making progress. The worker can also (optionally) specify details of the progress, for example percent complete, using the details parameter. This action can also be used by the worker as a mechanism to check if cancellation is being requested for the activity task. If a cancellation is being attempted for the specified task, then the boolean cancelRequested flag returned by the service is set to true.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RecordActivityTaskHeartbeat',
                ),
                'taskToken' => array(
                    'required' => true,
                    'description' => 'The taskToken of the ActivityTask.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'details' => array(
                    'description' => 'If specified, contains details about the progress of the task.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 2048,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'RegisterActivityType' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Registers a new activity type along with its configuration settings in the specified domain.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RegisterActivityType',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which this activity is to be registered.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'name' => array(
                    'required' => true,
                    'description' => 'The name of the activity type within the domain.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'version' => array(
                    'required' => true,
                    'description' => 'The version of the activity type.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'description' => array(
                    'description' => 'A textual description of the activity type.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
                'defaultTaskStartToCloseTimeout' => array(
                    'description' => 'If set, specifies the default maximum duration that a worker can take to process tasks of this activity type. This default can be overridden when scheduling an activity task using the ScheduleActivityTask Decision.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 8,
                ),
                'defaultTaskHeartbeatTimeout' => array(
                    'description' => 'If set, specifies the default maximum time before which a worker processing a task of this type must report progress by calling RecordActivityTaskHeartbeat. If the timeout is exceeded, the activity task is automatically timed out. This default can be overridden when scheduling an activity task using the ScheduleActivityTask Decision. If the activity worker subsequently attempts to record a heartbeat or returns a result, the activity worker receives an UnknownResource fault. In this case, Amazon SWF no longer considers the activity task to be valid; the activity worker should clean up the activity task.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 8,
                ),
                'defaultTaskList' => array(
                    'description' => 'If set, specifies the default task list to use for scheduling tasks of this activity type. This default task list is used if a task list is not provided when a task is scheduled through the ScheduleActivityTask Decision.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the task list.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'defaultTaskScheduleToStartTimeout' => array(
                    'description' => 'If set, specifies the default maximum duration that a task of this activity type can wait before being assigned to a worker. This default can be overridden when scheduling an activity task using the ScheduleActivityTask Decision.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 8,
                ),
                'defaultTaskScheduleToCloseTimeout' => array(
                    'description' => 'If set, specifies the default maximum duration for a task of this activity type. This default can be overridden when scheduling an activity task using the ScheduleActivityTask Decision.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 8,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the type already exists in the specified domain. You will get this fault even if the existing type is in deprecated status. You can specify another version if the intent is to create a new distinct version of the type.',
                    'class' => 'TypeAlreadyExistsException',
                ),
                array(
                    'reason' => 'Returned by any operation if a system imposed limitation has been reached. To address this fault you should either clean up unused resources or increase the limit by contacting AWS.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'RegisterDomain' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Registers a new domain.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RegisterDomain',
                ),
                'name' => array(
                    'required' => true,
                    'description' => 'Name of the domain to register. The name must be unique.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'description' => array(
                    'description' => 'Textual description of the domain.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
                'workflowExecutionRetentionPeriodInDays' => array(
                    'required' => true,
                    'description' => 'Specifies the duration--in days--for which the record (including the history) of workflow executions in this domain should be kept by the service. After the retention period, the workflow execution will not be available in the results of visibility calls. If a duration of NONE is specified, the records for workflow executions in this domain are not retained at all.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 8,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified domain already exists. You will get this fault even if the existing domain is in deprecated status.',
                    'class' => 'DomainAlreadyExistsException',
                ),
                array(
                    'reason' => 'Returned by any operation if a system imposed limitation has been reached. To address this fault you should either clean up unused resources or increase the limit by contacting AWS.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'RegisterWorkflowType' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Registers a new workflow type and its configuration settings in the specified domain.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RegisterWorkflowType',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which to register the workflow type.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'name' => array(
                    'required' => true,
                    'description' => 'The name of the workflow type.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'version' => array(
                    'required' => true,
                    'description' => 'The version of the workflow type.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'description' => array(
                    'description' => 'Textual description of the workflow type.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
                'defaultTaskStartToCloseTimeout' => array(
                    'description' => 'If set, specifies the default maximum duration of decision tasks for this workflow type. This default can be overridden when starting a workflow execution using the StartWorkflowExecution action or the StartChildWorkflowExecution Decision.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 8,
                ),
                'defaultExecutionStartToCloseTimeout' => array(
                    'description' => 'If set, specifies the default maximum duration for executions of this workflow type. You can override this default when starting an execution through the StartWorkflowExecution Action or StartChildWorkflowExecution Decision.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 8,
                ),
                'defaultTaskList' => array(
                    'description' => 'If set, specifies the default task list to use for scheduling decision tasks for executions of this workflow type. This default is used only if a task list is not provided when starting the execution through the StartWorkflowExecution Action or StartChildWorkflowExecution Decision.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the task list.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'defaultChildPolicy' => array(
                    'description' => 'If set, specifies the default policy to use for the child workflow executions when a workflow execution of this type is terminated, by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout. This default can be overridden when starting a workflow execution using the StartWorkflowExecution action or the StartChildWorkflowExecution Decision. The supported child policies are:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TERMINATE',
                        'REQUEST_CANCEL',
                        'ABANDON',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the type already exists in the specified domain. You will get this fault even if the existing type is in deprecated status. You can specify another version if the intent is to create a new distinct version of the type.',
                    'class' => 'TypeAlreadyExistsException',
                ),
                array(
                    'reason' => 'Returned by any operation if a system imposed limitation has been reached. To address this fault you should either clean up unused resources or increase the limit by contacting AWS.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'RequestCancelWorkflowExecution' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Records a WorkflowExecutionCancelRequested event in the currently running workflow execution identified by the given domain, workflowId, and runId. This logically requests the cancellation of the workflow execution as a whole. It is up to the decider to take appropriate actions when it receives an execution history with this event.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RequestCancelWorkflowExecution',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain containing the workflow execution to cancel.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'workflowId' => array(
                    'required' => true,
                    'description' => 'The workflowId of the workflow execution to cancel.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'runId' => array(
                    'description' => 'The runId of the workflow execution to cancel.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'RespondActivityTaskCanceled' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Used by workers to tell the service that the ActivityTask identified by the taskToken was successfully canceled. Additional details can be optionally provided using the details argument.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RespondActivityTaskCanceled',
                ),
                'taskToken' => array(
                    'required' => true,
                    'description' => 'The taskToken of the ActivityTask.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'details' => array(
                    'description' => 'Optional information about the cancellation.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 32768,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'RespondActivityTaskCompleted' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Used by workers to tell the service that the ActivityTask identified by the taskToken completed successfully with a result (if provided). The result appears in the ActivityTaskCompleted event in the workflow history.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RespondActivityTaskCompleted',
                ),
                'taskToken' => array(
                    'required' => true,
                    'description' => 'The taskToken of the ActivityTask.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'result' => array(
                    'description' => 'The result of the activity task. It is a free form string that is implementation specific.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 32768,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'RespondActivityTaskFailed' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Used by workers to tell the service that the ActivityTask identified by the taskToken has failed with reason (if specified). The reason and details appear in the ActivityTaskFailed event added to the workflow history.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RespondActivityTaskFailed',
                ),
                'taskToken' => array(
                    'required' => true,
                    'description' => 'The taskToken of the ActivityTask.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'reason' => array(
                    'description' => 'Description of the error that may assist in diagnostics.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 256,
                ),
                'details' => array(
                    'description' => 'Optional detailed information about the failure.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 32768,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'RespondDecisionTaskCompleted' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Used by deciders to tell the service that the DecisionTask identified by the taskToken has successfully completed. The decisions argument specifies the list of decisions made while processing the task.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.RespondDecisionTaskCompleted',
                ),
                'taskToken' => array(
                    'required' => true,
                    'description' => 'The taskToken from the DecisionTask.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'decisions' => array(
                    'description' => 'The list of decisions (possibly empty) made by the decider while processing this decision task. See the docs for the Decision structure for details.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Decision',
                        'description' => 'Specifies a decision made by the decider. A decision can be one of these types:',
                        'type' => 'object',
                        'properties' => array(
                            'decisionType' => array(
                                'required' => true,
                                'description' => 'Specifies the type of the decision.',
                                'type' => 'string',
                                'enum' => array(
                                    'ScheduleActivityTask',
                                    'RequestCancelActivityTask',
                                    'CompleteWorkflowExecution',
                                    'FailWorkflowExecution',
                                    'CancelWorkflowExecution',
                                    'ContinueAsNewWorkflowExecution',
                                    'RecordMarker',
                                    'StartTimer',
                                    'CancelTimer',
                                    'SignalExternalWorkflowExecution',
                                    'RequestCancelExternalWorkflowExecution',
                                    'StartChildWorkflowExecution',
                                ),
                            ),
                            'scheduleActivityTaskDecisionAttributes' => array(
                                'description' => 'Provides details of the ScheduleActivityTask decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'activityType' => array(
                                        'required' => true,
                                        'description' => 'The type of the activity task to schedule. This field is required.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'required' => true,
                                                'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 256,
                                            ),
                                            'version' => array(
                                                'required' => true,
                                                'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 64,
                                            ),
                                        ),
                                    ),
                                    'activityId' => array(
                                        'required' => true,
                                        'description' => 'The activityId of the activity task. This field is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks. This data is not sent to the activity.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                    'input' => array(
                                        'description' => 'The input provided to the activity task.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                    'scheduleToCloseTimeout' => array(
                                        'description' => 'The maximum duration for this activity task.',
                                        'type' => 'string',
                                        'maxLength' => 8,
                                    ),
                                    'taskList' => array(
                                        'description' => 'If set, specifies the name of the task list in which to schedule the activity task. If not specified, the defaultTaskList registered with the activity type will be used.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'required' => true,
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 256,
                                            ),
                                        ),
                                    ),
                                    'scheduleToStartTimeout' => array(
                                        'description' => 'If set, specifies the maximum duration the activity task can wait to be assigned to a worker. This overrides the default schedule-to-start timeout specified when registering the activity type using RegisterActivityType.',
                                        'type' => 'string',
                                        'maxLength' => 8,
                                    ),
                                    'startToCloseTimeout' => array(
                                        'description' => 'If set, specifies the maximum duration a worker may take to process this activity task. This overrides the default start-to-close timeout specified when registering the activity type using RegisterActivityType.',
                                        'type' => 'string',
                                        'maxLength' => 8,
                                    ),
                                    'heartbeatTimeout' => array(
                                        'description' => 'If set, specifies the maximum time before which a worker processing a task of this type must report progress by calling RecordActivityTaskHeartbeat. If the timeout is exceeded, the activity task is automatically timed out. If the worker subsequently attempts to record a heartbeat or returns a result, it will be ignored. This overrides the default heartbeat timeout specified when registering the activity type using RegisterActivityType.',
                                        'type' => 'string',
                                        'maxLength' => 8,
                                    ),
                                ),
                            ),
                            'requestCancelActivityTaskDecisionAttributes' => array(
                                'description' => 'Provides details of the RequestCancelActivityTask decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'activityId' => array(
                                        'required' => true,
                                        'description' => 'The activityId of the activity task to be canceled.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                ),
                            ),
                            'completeWorkflowExecutionDecisionAttributes' => array(
                                'description' => 'Provides details of the CompleteWorkflowExecution decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'result' => array(
                                        'description' => 'The result of the workflow execution. The form of the result is implementation defined.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                ),
                            ),
                            'failWorkflowExecutionDecisionAttributes' => array(
                                'description' => 'Provides details of the FailWorkflowExecution decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'reason' => array(
                                        'description' => 'A descriptive reason for the failure that may help in diagnostics.',
                                        'type' => 'string',
                                        'maxLength' => 256,
                                    ),
                                    'details' => array(
                                        'description' => 'Optional details of the failure.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                ),
                            ),
                            'cancelWorkflowExecutionDecisionAttributes' => array(
                                'description' => 'Provides details of the CancelWorkflowExecution decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'details' => array(
                                        'description' => 'Optional details of the cancellation.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                ),
                            ),
                            'continueAsNewWorkflowExecutionDecisionAttributes' => array(
                                'description' => 'Provides details of the ContinueAsNewWorkflowExecution decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'input' => array(
                                        'description' => 'The input provided to the new workflow execution.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                    'executionStartToCloseTimeout' => array(
                                        'description' => 'If set, specifies the total duration for this workflow execution. This overrides the defaultExecutionStartToCloseTimeout specified when registering the workflow type.',
                                        'type' => 'string',
                                        'maxLength' => 8,
                                    ),
                                    'taskList' => array(
                                        'description' => 'Represents a task list.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'required' => true,
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 256,
                                            ),
                                        ),
                                    ),
                                    'taskStartToCloseTimeout' => array(
                                        'description' => 'Specifies the maximum duration of decision tasks for the new workflow execution. This parameter overrides the defaultTaskStartToCloseTimout specified when registering the workflow type using RegisterWorkflowType.',
                                        'type' => 'string',
                                        'maxLength' => 8,
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'If set, specifies the policy to use for the child workflow executions of the new execution if it is terminated by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout. This policy overrides the default child policy specified when registering the workflow type using RegisterWorkflowType. The supported child policies are:',
                                        'type' => 'string',
                                        'enum' => array(
                                            'TERMINATE',
                                            'REQUEST_CANCEL',
                                            'ABANDON',
                                        ),
                                    ),
                                    'tagList' => array(
                                        'description' => 'The list of tags to associate with the new workflow execution. A maximum of 5 tags can be specified. You can list workflow executions with a specific tag by calling ListOpenWorkflowExecutions or ListClosedWorkflowExecutions and specifying a TagFilter.',
                                        'type' => 'array',
                                        'maxItems' => 5,
                                        'items' => array(
                                            'name' => 'Tag',
                                            'type' => 'string',
                                            'minLength' => 1,
                                            'maxLength' => 256,
                                        ),
                                    ),
                                    'workflowTypeVersion' => array(
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 64,
                                    ),
                                ),
                            ),
                            'recordMarkerDecisionAttributes' => array(
                                'description' => 'Provides details of the RecordMarker decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'markerName' => array(
                                        'required' => true,
                                        'description' => 'The name of the marker. This file is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                    'details' => array(
                                        'description' => 'Optional details of the marker.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                ),
                            ),
                            'startTimerDecisionAttributes' => array(
                                'description' => 'Provides details of the StartTimer decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'required' => true,
                                        'description' => 'The unique Id of the timer. This field is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                    'startToFireTimeout' => array(
                                        'required' => true,
                                        'description' => 'The duration to wait before firing the timer. This field is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 8,
                                    ),
                                ),
                            ),
                            'cancelTimerDecisionAttributes' => array(
                                'description' => 'Provides details of the CancelTimer decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'required' => true,
                                        'description' => 'The unique Id of the timer to cancel. This field is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                ),
                            ),
                            'signalExternalWorkflowExecutionDecisionAttributes' => array(
                                'description' => 'Provides details of the SignalExternalWorkflowExecution decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'required' => true,
                                        'description' => 'The workflowId of the workflow execution to be signaled. This field is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the workflow execution to be signaled.',
                                        'type' => 'string',
                                        'maxLength' => 64,
                                    ),
                                    'signalName' => array(
                                        'required' => true,
                                        'description' => 'The name of the signal.The target workflow execution will use the signal name and input to process the signal. This field is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                    'input' => array(
                                        'description' => 'Optional input to be provided with the signal.The target workflow execution will use the signal name and input to process the signal.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent decision tasks.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                ),
                            ),
                            'requestCancelExternalWorkflowExecutionDecisionAttributes' => array(
                                'description' => 'Provides details of the RequestCancelExternalWorkflowExecution decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'required' => true,
                                        'description' => 'The workflowId of the external workflow execution to cancel. This field is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution to cancel.',
                                        'type' => 'string',
                                        'maxLength' => 64,
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                ),
                            ),
                            'startChildWorkflowExecutionDecisionAttributes' => array(
                                'description' => 'Provides details of the StartChildWorkflowExecution decision. It is not set for other decision types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowType' => array(
                                        'required' => true,
                                        'description' => 'The type of the workflow execution to be started. This field is required.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'required' => true,
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 256,
                                            ),
                                            'version' => array(
                                                'required' => true,
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 64,
                                            ),
                                        ),
                                    ),
                                    'workflowId' => array(
                                        'required' => true,
                                        'description' => 'The workflowId of the workflow execution. This field is required.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks. This data is not sent to the child workflow execution.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                    'input' => array(
                                        'description' => 'The input to be provided to the workflow execution.',
                                        'type' => 'string',
                                        'maxLength' => 32768,
                                    ),
                                    'executionStartToCloseTimeout' => array(
                                        'description' => 'The total duration for this workflow execution. This overrides the defaultExecutionStartToCloseTimeout specified when registering the workflow type.',
                                        'type' => 'string',
                                        'maxLength' => 8,
                                    ),
                                    'taskList' => array(
                                        'description' => 'The name of the task list to be used for decision tasks of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'required' => true,
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 256,
                                            ),
                                        ),
                                    ),
                                    'taskStartToCloseTimeout' => array(
                                        'description' => 'Specifies the maximum duration of decision tasks for this workflow execution. This parameter overrides the defaultTaskStartToCloseTimout specified when registering the workflow type using RegisterWorkflowType.',
                                        'type' => 'string',
                                        'maxLength' => 8,
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'If set, specifies the policy to use for the child workflow executions if the workflow execution being started is terminated by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout. This policy overrides the default child policy specified when registering the workflow type using RegisterWorkflowType. The supported child policies are:',
                                        'type' => 'string',
                                        'enum' => array(
                                            'TERMINATE',
                                            'REQUEST_CANCEL',
                                            'ABANDON',
                                        ),
                                    ),
                                    'tagList' => array(
                                        'description' => 'The list of tags to associate with the child workflow execution. A maximum of 5 tags can be specified. You can list workflow executions with a specific tag by calling ListOpenWorkflowExecutions or ListClosedWorkflowExecutions and specifying a TagFilter.',
                                        'type' => 'array',
                                        'maxItems' => 5,
                                        'items' => array(
                                            'name' => 'Tag',
                                            'type' => 'string',
                                            'minLength' => 1,
                                            'maxLength' => 256,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'executionContext' => array(
                    'description' => 'User defined context to add to workflow execution.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 32768,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'SignalWorkflowExecution' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Records a WorkflowExecutionSignaled event in the workflow execution history and creates a decision task for the workflow execution identified by the given domain, workflowId and runId. The event is recorded with the specified user defined signalName and input (if provided).',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.SignalWorkflowExecution',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain containing the workflow execution to signal.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'workflowId' => array(
                    'required' => true,
                    'description' => 'The workflowId of the workflow execution to signal.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'runId' => array(
                    'description' => 'The runId of the workflow execution to signal.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 64,
                ),
                'signalName' => array(
                    'required' => true,
                    'description' => 'The name of the signal. This name must be meaningful to the target workflow.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'input' => array(
                    'description' => 'Data to attach to the WorkflowExecutionSignaled event in the target workflow execution\'s history.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 32768,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
        'StartWorkflowExecution' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'Run',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Starts an execution of the workflow type in the specified domain using the provided workflowId and input data.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.StartWorkflowExecution',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The name of the domain in which the workflow execution is created.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'workflowId' => array(
                    'required' => true,
                    'description' => 'The user defined identifier associated with the workflow execution. You can use this to associate a custom identifier with the workflow execution. You may specify the same identifier if a workflow execution is logically a restart of a previous execution. You cannot have two open workflow executions with the same workflowId at the same time.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'workflowType' => array(
                    'required' => true,
                    'description' => 'The type of the workflow to start.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'version' => array(
                            'required' => true,
                            'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 64,
                        ),
                    ),
                ),
                'taskList' => array(
                    'description' => 'The task list to use for the decision tasks generated for this workflow execution. This overrides the defaultTaskList specified when registering the workflow type.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'required' => true,
                            'description' => 'The name of the task list.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'input' => array(
                    'description' => 'The input for the workflow execution. This is a free form string which should be meaningful to the workflow you are starting. This input is made available to the new workflow execution in the WorkflowExecutionStarted history event.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 32768,
                ),
                'executionStartToCloseTimeout' => array(
                    'description' => 'The total duration for this workflow execution. This overrides the defaultExecutionStartToCloseTimeout specified when registering the workflow type.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 8,
                ),
                'tagList' => array(
                    'description' => 'The list of tags to associate with the workflow execution. You can specify a maximum of 5 tags. You can list workflow executions with a specific tag by calling ListOpenWorkflowExecutions or ListClosedWorkflowExecutions and specifying a TagFilter.',
                    'type' => 'array',
                    'location' => 'json',
                    'maxItems' => 5,
                    'items' => array(
                        'name' => 'Tag',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 256,
                    ),
                ),
                'taskStartToCloseTimeout' => array(
                    'description' => 'Specifies the maximum duration of decision tasks for this workflow execution. This parameter overrides the defaultTaskStartToCloseTimout specified when registering the workflow type using RegisterWorkflowType.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 8,
                ),
                'childPolicy' => array(
                    'description' => 'If set, specifies the policy to use for the child workflow executions of this workflow execution if it is terminated, by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout. This policy overrides the default child policy specified when registering the workflow type using RegisterWorkflowType. The supported child policies are:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TERMINATE',
                        'REQUEST_CANCEL',
                        'ABANDON',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the specified activity or workflow type was already deprecated.',
                    'class' => 'TypeDeprecatedException',
                ),
                array(
                    'reason' => 'Returned by StartWorkflowExecution when an open execution with the same workflowId is already running in the specified domain.',
                    'class' => 'WorkflowExecutionAlreadyStartedException',
                ),
                array(
                    'reason' => 'Returned by any operation if a system imposed limitation has been reached. To address this fault you should either clean up unused resources or increase the limit by contacting AWS.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
                array(
                    'class' => 'DefaultUndefinedException',
                ),
            ),
        ),
        'TerminateWorkflowExecution' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Records a WorkflowExecutionTerminated event and forces closure of the workflow execution identified by the given domain, runId, and workflowId. The child policy, registered with the workflow type or specified when starting this execution, is applied to any open child workflow executions of this workflow execution.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.0',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'SimpleWorkflowService.TerminateWorkflowExecution',
                ),
                'domain' => array(
                    'required' => true,
                    'description' => 'The domain of the workflow execution to terminate.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'workflowId' => array(
                    'required' => true,
                    'description' => 'The workflowId of the workflow execution to terminate.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 256,
                ),
                'runId' => array(
                    'description' => 'The runId of the workflow execution to terminate.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 64,
                ),
                'reason' => array(
                    'description' => 'An optional descriptive reason for terminating the workflow execution.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 256,
                ),
                'details' => array(
                    'description' => 'Optional details for terminating the workflow execution.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 32768,
                ),
                'childPolicy' => array(
                    'description' => 'If set, specifies the policy to use for the child workflow executions of the workflow execution being terminated. This policy overrides the child policy specified for the workflow execution at registration time or when starting the execution. The supported child policies are:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'TERMINATE',
                        'REQUEST_CANCEL',
                        'ABANDON',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned when the named resource cannot be found with in the scope of this operation (region or domain). This could happen if the named resource was never created or is no longer available for this operation.',
                    'class' => 'UnknownResourceException',
                ),
                array(
                    'reason' => 'Returned when the caller does not have sufficient permissions to invoke the action.',
                    'class' => 'OperationNotPermittedException',
                ),
            ),
        ),
    ),
    'models' => array(
        'WorkflowExecutionCount' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'count' => array(
                    'description' => 'The number of workflow executions.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'truncated' => array(
                    'description' => 'If set to true, indicates that the actual count was more than the maximum supported by this API and the count returned is the truncated value.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'PendingTaskCount' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'count' => array(
                    'description' => 'The number of tasks in the task list.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'truncated' => array(
                    'description' => 'If set to true, indicates that the actual count was more than the maximum supported by this API and the count returned is the truncated value.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'ActivityTypeDetail' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'typeInfo' => array(
                    'description' => 'General information about the activity type.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'activityType' => array(
                            'description' => 'The ActivityType type structure representing the activity type.',
                            'type' => 'object',
                            'properties' => array(
                                'name' => array(
                                    'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                                    'type' => 'string',
                                ),
                                'version' => array(
                                    'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'status' => array(
                            'description' => 'The current status of the activity type.',
                            'type' => 'string',
                        ),
                        'description' => array(
                            'description' => 'The description of the activity type provided in RegisterActivityType.',
                            'type' => 'string',
                        ),
                        'creationDate' => array(
                            'description' => 'The date and time this activity type was created through RegisterActivityType.',
                            'type' => 'string',
                        ),
                        'deprecationDate' => array(
                            'description' => 'If DEPRECATED, the date and time DeprecateActivityType was called.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'configuration' => array(
                    'description' => 'The configuration settings registered with the activity type.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'defaultTaskStartToCloseTimeout' => array(
                            'description' => 'The optional default maximum duration for tasks of an activity type specified when registering the activity type. You can override this default when scheduling a task through the ScheduleActivityTask Decision.',
                            'type' => 'string',
                        ),
                        'defaultTaskHeartbeatTimeout' => array(
                            'description' => 'The optional default maximum time, specified when registering the activity type, before which a worker processing a task must report progress by calling RecordActivityTaskHeartbeat. You can override this default when scheduling a task through the ScheduleActivityTask Decision. If the activity worker subsequently attempts to record a heartbeat or returns a result, the activity worker receives an UnknownResource fault. In this case, Amazon SWF no longer considers the activity task to be valid; the activity worker should clean up the activity task.',
                            'type' => 'string',
                        ),
                        'defaultTaskList' => array(
                            'description' => 'The optional default task list specified for this activity type at registration. This default task list is used if a task list is not provided when a task is scheduled through the ScheduleActivityTask Decision. You can override this default when scheduling a task through the ScheduleActivityTask Decision.',
                            'type' => 'object',
                            'properties' => array(
                                'name' => array(
                                    'description' => 'The name of the task list.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'defaultTaskScheduleToStartTimeout' => array(
                            'description' => 'The optional default maximum duration, specified when registering the activity type, that a task of an activity type can wait before being assigned to a worker. You can override this default when scheduling a task through the ScheduleActivityTask Decision.',
                            'type' => 'string',
                        ),
                        'defaultTaskScheduleToCloseTimeout' => array(
                            'description' => 'The optional default maximum duration, specified when registering the activity type, for tasks of this activity type. You can override this default when scheduling a task through the ScheduleActivityTask Decision.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'DomainDetail' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'domainInfo' => array(
                    'description' => 'Contains general information about a domain.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'description' => 'The name of the domain. This name is unique within the account.',
                            'type' => 'string',
                        ),
                        'status' => array(
                            'description' => 'The status of the domain:',
                            'type' => 'string',
                        ),
                        'description' => array(
                            'description' => 'The description of the domain provided through RegisterDomain.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'configuration' => array(
                    'description' => 'Contains the configuration settings of a domain.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowExecutionRetentionPeriodInDays' => array(
                            'description' => 'The retention period for workflow executions in this domain.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'WorkflowExecutionDetail' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'executionInfo' => array(
                    'description' => 'Information about the workflow execution.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'execution' => array(
                            'description' => 'The workflow execution this information is about.',
                            'type' => 'object',
                            'properties' => array(
                                'workflowId' => array(
                                    'description' => 'The user defined identifier associated with the workflow execution.',
                                    'type' => 'string',
                                ),
                                'runId' => array(
                                    'description' => 'A system generated unique identifier for the workflow execution.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'workflowType' => array(
                            'description' => 'The type of the workflow execution.',
                            'type' => 'object',
                            'properties' => array(
                                'name' => array(
                                    'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                    'type' => 'string',
                                ),
                                'version' => array(
                                    'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'startTimestamp' => array(
                            'description' => 'The time when the execution was started.',
                            'type' => 'string',
                        ),
                        'closeTimestamp' => array(
                            'description' => 'The time when the workflow execution was closed. Set only if the execution status is CLOSED.',
                            'type' => 'string',
                        ),
                        'executionStatus' => array(
                            'description' => 'The current status of the execution.',
                            'type' => 'string',
                        ),
                        'closeStatus' => array(
                            'description' => 'If the execution status is closed then this specifies how the execution was closed:',
                            'type' => 'string',
                        ),
                        'parent' => array(
                            'description' => 'If this workflow execution is a child of another execution then contains the workflow execution that started this execution.',
                            'type' => 'object',
                            'properties' => array(
                                'workflowId' => array(
                                    'description' => 'The user defined identifier associated with the workflow execution.',
                                    'type' => 'string',
                                ),
                                'runId' => array(
                                    'description' => 'A system generated unique identifier for the workflow execution.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'tagList' => array(
                            'description' => 'The list of tags associated with the workflow execution. Tags can be used to identify and list workflow executions of interest through the visibility APIs. A workflow execution can have a maximum of 5 tags.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Tag',
                                'type' => 'string',
                            ),
                        ),
                        'cancelRequested' => array(
                            'description' => 'Set to true if a cancellation is requested for this workflow execution.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
                'executionConfiguration' => array(
                    'description' => 'The configuration settings for this workflow execution including timeout values, tasklist etc.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'taskStartToCloseTimeout' => array(
                            'description' => 'The maximum duration allowed for decision tasks for this workflow execution.',
                            'type' => 'string',
                        ),
                        'executionStartToCloseTimeout' => array(
                            'description' => 'The total duration for this workflow execution.',
                            'type' => 'string',
                        ),
                        'taskList' => array(
                            'description' => 'The task list used for the decision tasks generated for this workflow execution.',
                            'type' => 'object',
                            'properties' => array(
                                'name' => array(
                                    'description' => 'The name of the task list.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'childPolicy' => array(
                            'description' => 'The policy to use for the child workflow executions if this workflow execution is terminated, by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout. The supported child policies are:',
                            'type' => 'string',
                        ),
                    ),
                ),
                'openCounts' => array(
                    'description' => 'The number of tasks for this workflow execution. This includes open and closed tasks of all types.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'openActivityTasks' => array(
                            'description' => 'The count of activity tasks whose status is OPEN.',
                            'type' => 'numeric',
                        ),
                        'openDecisionTasks' => array(
                            'description' => 'The count of decision tasks whose status is OPEN. A workflow execution can have at most one open decision task.',
                            'type' => 'numeric',
                        ),
                        'openTimers' => array(
                            'description' => 'The count of timers started by this workflow execution that have not fired yet.',
                            'type' => 'numeric',
                        ),
                        'openChildWorkflowExecutions' => array(
                            'description' => 'The count of child workflow executions whose status is OPEN.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'latestActivityTaskTimestamp' => array(
                    'description' => 'The time when the last activity task was scheduled for this workflow execution. You can use this information to determine if the workflow has not made progress for an unusually long period of time and might require a corrective action.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'latestExecutionContext' => array(
                    'description' => 'The latest executionContext provided by the decider for this workflow execution. A decider can provide an executionContext, which is a free form string, when closing a decision task using RespondDecisionTaskCompleted.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'WorkflowTypeDetail' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'typeInfo' => array(
                    'description' => 'General information about the workflow type.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowType' => array(
                            'description' => 'The workflow type this information is about.',
                            'type' => 'object',
                            'properties' => array(
                                'name' => array(
                                    'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                    'type' => 'string',
                                ),
                                'version' => array(
                                    'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'status' => array(
                            'description' => 'The current status of the workflow type.',
                            'type' => 'string',
                        ),
                        'description' => array(
                            'description' => 'The description of the type registered through RegisterWorkflowType.',
                            'type' => 'string',
                        ),
                        'creationDate' => array(
                            'description' => 'The date when this type was registered.',
                            'type' => 'string',
                        ),
                        'deprecationDate' => array(
                            'description' => 'If the type is in deprecated state, then it is set to the date when the type was deprecated.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'configuration' => array(
                    'description' => 'Configuration settings of the workflow type registered through RegisterWorkflowType',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'defaultTaskStartToCloseTimeout' => array(
                            'description' => 'The optional default maximum duration, specified when registering the workflow type, that a decision task for executions of this workflow type might take before returning completion or failure. If the task does not close in the specified time then the task is automatically timed out and rescheduled. If the decider eventually reports a completion or failure, it is ignored. This default can be overridden when starting a workflow execution using the StartWorkflowExecution action or the StartChildWorkflowExecution Decision.',
                            'type' => 'string',
                        ),
                        'defaultExecutionStartToCloseTimeout' => array(
                            'description' => 'The optional default maximum duration, specified when registering the workflow type, for executions of this workflow type. This default can be overridden when starting a workflow execution using the StartWorkflowExecution action or the StartChildWorkflowExecution Decision.',
                            'type' => 'string',
                        ),
                        'defaultTaskList' => array(
                            'description' => 'The optional default task list, specified when registering the workflow type, for decisions tasks scheduled for workflow executions of this type. This default can be overridden when starting a workflow execution using the StartWorkflowExecution action or the StartChildWorkflowExecution Decision.',
                            'type' => 'object',
                            'properties' => array(
                                'name' => array(
                                    'description' => 'The name of the task list.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'defaultChildPolicy' => array(
                            'description' => 'The optional default policy to use for the child workflow executions when a workflow execution of this type is terminated, by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout. This default can be overridden when starting a workflow execution using the StartWorkflowExecution action or the StartChildWorkflowExecution Decision. The supported child policies are:',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'History' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'events' => array(
                    'description' => 'The list of history events.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'HistoryEvent',
                        'description' => 'Event within a workflow execution. A history event can be one of these types:',
                        'type' => 'object',
                        'properties' => array(
                            'eventTimestamp' => array(
                                'description' => 'The date and time when the event occurred.',
                                'type' => 'string',
                            ),
                            'eventType' => array(
                                'description' => 'The type of the history event.',
                                'type' => 'string',
                            ),
                            'eventId' => array(
                                'description' => 'The system generated id of the event. This id uniquely identifies the event with in the workflow execution history.',
                                'type' => 'numeric',
                            ),
                            'workflowExecutionStartedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'input' => array(
                                        'description' => 'The input provided to the workflow execution (if any).',
                                        'type' => 'string',
                                    ),
                                    'executionStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration for this workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'taskStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration of decision tasks for this workflow type.',
                                        'type' => 'string',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy to use for the child workflow executions if this workflow execution is terminated, by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout. The supported child policies are: TERMINATE: the child executions will be terminated. REQUEST_CANCEL: a request to cancel will be attempted for each child execution by recording a WorkflowExecutionCancelRequested event in its history. It is up to the decider to take appropriate actions when it receives an execution history with this event. ABANDON: no action will be taken. The child executions will continue to run.',
                                        'type' => 'string',
                                    ),
                                    'taskList' => array(
                                        'description' => 'The name of the task list for scheduling the decision tasks for this workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The workflow type of this execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'tagList' => array(
                                        'description' => 'The list of tags associated with this workflow execution. An execution can have up to 5 tags.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Tag',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'continuedExecutionRunId' => array(
                                        'description' => 'If this workflow execution was started due to a ContinueAsNewWorkflowExecution decision, then it contains the runId of the previous workflow execution that was closed and continued as this execution.',
                                        'type' => 'string',
                                    ),
                                    'parentWorkflowExecution' => array(
                                        'description' => 'The source workflow execution that started this workflow execution. The member is not set if the workflow execution was not started by a workflow.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'parentInitiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this workflow execution. The source event with this Id can be found in the history of the source workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionCompletedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionCompleted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'result' => array(
                                        'description' => 'The result produced by the workflow execution upon successful completion.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CompleteWorkflowExecution decision to complete this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'completeWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type CompleteWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'cause' => array(
                                        'description' => 'The cause of the failure. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CompleteWorkflowExecution decision to complete this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'reason' => array(
                                        'description' => 'The descriptive reason provided for the failure (if any).',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'The details of the failure (if any).',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the FailWorkflowExecution decision to fail this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'failWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type FailWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'cause' => array(
                                        'description' => 'The cause of the failure. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the FailWorkflowExecution decision to fail this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionTimedOutEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionTimedOut then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timeoutType' => array(
                                        'description' => 'The type of timeout that caused this event.',
                                        'type' => 'string',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy used for the child workflow executions of this workflow execution. The supported child policies are: TERMINATE: the child executions will be terminated. REQUEST_CANCEL: a request to cancel will be attempted for each child execution by recording a WorkflowExecutionCancelRequested event in its history. It is up to the decider to take appropriate actions when it receives an execution history with this event. ABANDON: no action will be taken. The child executions will continue to run.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'workflowExecutionCanceledEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionCanceled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'details' => array(
                                        'description' => 'Details for the cancellation (if any).',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CancelWorkflowExecution decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'cancelWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type CancelWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'cause' => array(
                                        'description' => 'The cause of the failure. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CancelWorkflowExecution decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionContinuedAsNewEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionContinuedAsNew then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'input' => array(
                                        'description' => 'The input provided to the new workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the ContinueAsNewWorkflowExecution decision that started this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'newExecutionRunId' => array(
                                        'description' => 'The runId of the new workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'executionStartToCloseTimeout' => array(
                                        'description' => 'The total duration allowed for the new workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'taskList' => array(
                                        'description' => 'Represents a task list.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'taskStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration of decision tasks for the new workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy to use for the child workflow executions of the new execution if it is terminated by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout.',
                                        'type' => 'string',
                                    ),
                                    'tagList' => array(
                                        'description' => 'The list of tags associated with the new workflow execution.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Tag',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'Represents a workflow type.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'continueAsNewWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type ContinueAsNewWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'cause' => array(
                                        'description' => 'The cause of the failure. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the ContinueAsNewWorkflowExecution decision that started this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionTerminatedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionTerminated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'reason' => array(
                                        'description' => 'The reason provided for the termination (if any).',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'The details provided for the termination (if any).',
                                        'type' => 'string',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy used for the child workflow executions of this workflow execution. The supported child policies are:',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'If set, indicates that the workflow execution was automatically terminated, and specifies the cause. This happens if the parent workflow execution times out or is terminated and the child policy is set to terminate child executions.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'workflowExecutionCancelRequestedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionCancelRequested then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'externalWorkflowExecution' => array(
                                        'description' => 'The external workflow execution for which the cancellation was requested.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'externalInitiatedEventId' => array(
                                        'description' => 'The id of the RequestCancelExternalWorkflowExecutionInitiated event corresponding to the RequestCancelExternalWorkflowExecution decision to cancel this workflow execution.The source event with this Id can be found in the history of the source workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'cause' => array(
                                        'description' => 'If set, indicates that the request to cancel the workflow execution was automatically generated, and specifies the cause. This happens if the parent workflow execution times out or is terminated, and the child policy is set to cancel child executions.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'decisionTaskScheduledEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskScheduled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'taskList' => array(
                                        'description' => 'The name of the task list in which the decision task was scheduled.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'startToCloseTimeout' => array(
                                        'description' => 'The maximum duration for this decision task. The task is considered timed out if it does not completed within this duration.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'decisionTaskStartedEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'identity' => array(
                                        'description' => 'Identity of the decider making the request. This enables diagnostic tracing when problems arise. The form of this identity is user defined.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the DecisionTaskScheduled event that was recorded when this decision task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'decisionTaskCompletedEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskCompleted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'executionContext' => array(
                                        'description' => 'User defined context for the workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the DecisionTaskScheduled event that was recorded when this decision task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the DecisionTaskStarted event recorded when this decision task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'decisionTaskTimedOutEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskTimedOut then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timeoutType' => array(
                                        'description' => 'The type of timeout that expired before the decision task could be completed.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the DecisionTaskScheduled event that was recorded when this decision task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the DecisionTaskStarted event recorded when this decision task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskScheduledEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskScheduled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'activityType' => array(
                                        'description' => 'The type of the activity task.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'activityId' => array(
                                        'description' => 'The unique id of the activity task.',
                                        'type' => 'string',
                                    ),
                                    'input' => array(
                                        'description' => 'The input provided to the activity task.',
                                        'type' => 'string',
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks. This data is not sent to the activity.',
                                        'type' => 'string',
                                    ),
                                    'scheduleToStartTimeout' => array(
                                        'description' => 'The maximum amount of time the activity task can wait to be assigned to a worker.',
                                        'type' => 'string',
                                    ),
                                    'scheduleToCloseTimeout' => array(
                                        'description' => 'The maximum amount of time for this activity task.',
                                        'type' => 'string',
                                    ),
                                    'startToCloseTimeout' => array(
                                        'description' => 'The maximum amount of time a worker may take to process the activity task.',
                                        'type' => 'string',
                                    ),
                                    'taskList' => array(
                                        'description' => 'The task list in which the activity task has been scheduled.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision that resulted in the scheduling of this activity task. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'heartbeatTimeout' => array(
                                        'description' => 'The maximum time before which the worker processing this task must report progress by calling RecordActivityTaskHeartbeat. If the timeout is exceeded, the activity task is automatically timed out. If the worker subsequently attempts to record a heartbeat or return a result, it will be ignored.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'activityTaskStartedEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'identity' => array(
                                        'description' => 'Identity of the worker that was assigned this task. This aids diagnostics when problems arise. The form of this identity is user defined.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskCompletedEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskCompleted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'result' => array(
                                        'description' => 'The results of the activity task (if any).',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ActivityTaskStarted event recorded when this activity task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskFailedEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'reason' => array(
                                        'description' => 'The reason provided for the failure (if any).',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'The details of the failure (if any).',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ActivityTaskStarted event recorded when this activity task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskTimedOutEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskTimedOut then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timeoutType' => array(
                                        'description' => 'The type of the timeout that caused this event.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ActivityTaskStarted event recorded when this activity task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'details' => array(
                                        'description' => 'Contains the content of the details parameter for the last call made by the activity to RecordActivityTaskHeartbeat.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'activityTaskCanceledEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskCanceled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'details' => array(
                                        'description' => 'Details of the cancellation (if any).',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ActivityTaskStarted event recorded when this activity task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'latestCancelRequestedEventId' => array(
                                        'description' => 'If set, contains the Id of the last ActivityTaskCancelRequested event recorded for this activity task. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskCancelRequestedEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskcancelRequested then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RequestCancelActivityTask decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'activityId' => array(
                                        'description' => 'The unique ID of the task.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'workflowExecutionSignaledEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionSignaled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'signalName' => array(
                                        'description' => 'The name of the signal received. The decider can use the signal name and inputs to determine how to the process the signal.',
                                        'type' => 'string',
                                    ),
                                    'input' => array(
                                        'description' => 'Inputs provided with the signal (if any). The decider can use the signal name and inputs to determine how to process the signal.',
                                        'type' => 'string',
                                    ),
                                    'externalWorkflowExecution' => array(
                                        'description' => 'The workflow execution that sent the signal. This is set only of the signal was sent by another workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'externalInitiatedEventId' => array(
                                        'description' => 'The id of the SignalExternalWorkflowExecutionInitiated event corresponding to the SignalExternalWorkflow decision to signal this workflow execution.The source event with this Id can be found in the history of the source workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event. This field is set only if the signal was initiated by another workflow execution.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'markerRecordedEventAttributes' => array(
                                'description' => 'If the event is of type MarkerRecorded then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'markerName' => array(
                                        'description' => 'The name of the marker.',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'Details of the marker (if any).',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RecordMarker decision that requested this marker. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'recordMarkerFailedEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'markerName' => array(
                                        'description' => 'The marker\'s name.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RecordMarkerFailed decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'timerStartedEventAttributes' => array(
                                'description' => 'If the event is of type TimerStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The unique Id of the timer that was started.',
                                        'type' => 'string',
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks.',
                                        'type' => 'string',
                                    ),
                                    'startToFireTimeout' => array(
                                        'description' => 'The duration of time after which the timer will fire.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the StartTimer decision for this activity task. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'timerFiredEventAttributes' => array(
                                'description' => 'If the event is of type TimerFired then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The unique Id of the timer that fired.',
                                        'type' => 'string',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The id of the TimerStarted event that was recorded when this timer was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'timerCanceledEventAttributes' => array(
                                'description' => 'If the event is of type TimerCanceled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The unique Id of the timer that was canceled.',
                                        'type' => 'string',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The id of the TimerStarted event that was recorded when this timer was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CancelTimer decision to cancel this timer. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'startChildWorkflowExecutionInitiatedEventAttributes' => array(
                                'description' => 'If the event is of type StartChildWorkflowExecutionInitiated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the child workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent decision tasks. This data is not sent to the activity.',
                                        'type' => 'string',
                                    ),
                                    'input' => array(
                                        'description' => 'The inputs provided to the child workflow execution (if any).',
                                        'type' => 'string',
                                    ),
                                    'executionStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration for the child workflow execution. If the workflow execution is not closed within this duration, it will be timed out and force terminated.',
                                        'type' => 'string',
                                    ),
                                    'taskList' => array(
                                        'description' => 'The name of the task list used for the decision tasks of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the StartChildWorkflowExecution Decision to request this child workflow execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy to use for the child workflow executions if this execution gets terminated by explicitly calling the TerminateWorkflowExecution action or due to an expired timeout.',
                                        'type' => 'string',
                                    ),
                                    'taskStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration allowed for the decision tasks for this workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'tagList' => array(
                                        'description' => 'The list of tags to associated with the child workflow execution.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Tag',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionStartedEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that was started.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionCompletedEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionCompleted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that was completed.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'result' => array(
                                        'description' => 'The result of the child workflow execution (if any).',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that failed.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'reason' => array(
                                        'description' => 'The reason for the failure (if provided).',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'The details of the failure (if provided).',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionTimedOutEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionTimedOut then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that timed out.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'timeoutType' => array(
                                        'description' => 'The type of the timeout that caused the child workflow execution to time out.',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionCanceledEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionCanceled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that was canceled.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'details' => array(
                                        'description' => 'Details of the cancellation (if provided).',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionTerminatedEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionTerminated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that was terminated.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'signalExternalWorkflowExecutionInitiatedEventAttributes' => array(
                                'description' => 'If the event is of type SignalExternalWorkflowExecutionInitiated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the external workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution to send the signal to.',
                                        'type' => 'string',
                                    ),
                                    'signalName' => array(
                                        'description' => 'The name of the signal.',
                                        'type' => 'string',
                                    ),
                                    'input' => array(
                                        'description' => 'Input provided to the signal (if any).',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the SignalExternalWorkflowExecution decision for this signal. This information can be useful for diagnosing problems by tracing back the cause of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent decision tasks.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'externalWorkflowExecutionSignaledEventAttributes' => array(
                                'description' => 'If the event is of type ExternalWorkflowExecutionSignaled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The external workflow execution that the signal was delivered to.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the SignalExternalWorkflowExecutionInitiated event corresponding to the SignalExternalWorkflowExecution decision to request this signal. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'signalExternalWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type SignalExternalWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the external workflow execution that the signal was being delivered to.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution that the signal was being delivered to.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the SignalExternalWorkflowExecutionInitiated event corresponding to the SignalExternalWorkflowExecution decision to request this signal. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the SignalExternalWorkflowExecution decision for this signal. This information can be useful for diagnosing problems by tracing back the cause of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'externalWorkflowExecutionCancelRequestedEventAttributes' => array(
                                'description' => 'If the event is of type ExternalWorkflowExecutionCancelRequested then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The external workflow execution to which the cancellation request was delivered.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the RequestCancelExternalWorkflowExecutionInitiated event corresponding to the RequestCancelExternalWorkflowExecution decision to cancel this external workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'requestCancelExternalWorkflowExecutionInitiatedEventAttributes' => array(
                                'description' => 'If the event is of type RequestCancelExternalWorkflowExecutionInitiated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the external workflow execution to be canceled.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution to be canceled.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RequestCancelExternalWorkflowExecution decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'requestCancelExternalWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type RequestCancelExternalWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the external workflow to which the cancel request was to be delivered.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the RequestCancelExternalWorkflowExecutionInitiated event corresponding to the RequestCancelExternalWorkflowExecution decision to cancel this external workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RequestCancelExternalWorkflowExecution decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'scheduleActivityTaskFailedEventAttributes' => array(
                                'description' => 'If the event is of type ScheduleActivityTaskFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'activityType' => array(
                                        'description' => 'The activity type provided in the ScheduleActivityTask decision that failed.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'activityId' => array(
                                        'description' => 'The activityId provided in the ScheduleActivityTask decision that failed.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision that resulted in the scheduling of this activity task. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'requestCancelActivityTaskFailedEventAttributes' => array(
                                'description' => 'If the event is of type RequestCancelActivityTaskFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'activityId' => array(
                                        'description' => 'The activityId provided in the RequestCancelActivityTask decision that failed.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RequestCancelActivityTask decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'startTimerFailedEventAttributes' => array(
                                'description' => 'If the event is of type StartTimerFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The timerId provided in the StartTimer decision that failed.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the StartTimer decision for this activity task. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'cancelTimerFailedEventAttributes' => array(
                                'description' => 'If the event is of type CancelTimerFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The timerId provided in the CancelTimer decision that failed.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CancelTimer decision to cancel this timer. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'startChildWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type StartChildWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowType' => array(
                                        'description' => 'The workflow type provided in the StartChildWorkflowExecution Decision that failed.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the child workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the StartChildWorkflowExecution Decision to request this child workflow execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'The token for the next page. If set, the history consists of more than one page and the next page can be retrieved by repeating the request with this token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ActivityTypeInfos' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'typeInfos' => array(
                    'description' => 'List of activity type information.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ActivityTypeInfo',
                        'description' => 'Detailed information about an activity type.',
                        'type' => 'object',
                        'properties' => array(
                            'activityType' => array(
                                'description' => 'The ActivityType type structure representing the activity type.',
                                'type' => 'object',
                                'properties' => array(
                                    'name' => array(
                                        'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                                        'type' => 'string',
                                    ),
                                    'version' => array(
                                        'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'status' => array(
                                'description' => 'The current status of the activity type.',
                                'type' => 'string',
                            ),
                            'description' => array(
                                'description' => 'The description of the activity type provided in RegisterActivityType.',
                                'type' => 'string',
                            ),
                            'creationDate' => array(
                                'description' => 'The date and time this activity type was created through RegisterActivityType.',
                                'type' => 'string',
                            ),
                            'deprecationDate' => array(
                                'description' => 'If DEPRECATED, the date and time DeprecateActivityType was called.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'Returns a value if the results are paginated. To get the next page of results, repeat the request specifying this token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'WorkflowExecutionInfos' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'executionInfos' => array(
                    'description' => 'The list of workflow information structures.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'WorkflowExecutionInfo',
                        'description' => 'Contains information about a workflow execution.',
                        'type' => 'object',
                        'properties' => array(
                            'execution' => array(
                                'description' => 'The workflow execution this information is about.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The user defined identifier associated with the workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'A system generated unique identifier for the workflow execution.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'workflowType' => array(
                                'description' => 'The type of the workflow execution.',
                                'type' => 'object',
                                'properties' => array(
                                    'name' => array(
                                        'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                        'type' => 'string',
                                    ),
                                    'version' => array(
                                        'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'startTimestamp' => array(
                                'description' => 'The time when the execution was started.',
                                'type' => 'string',
                            ),
                            'closeTimestamp' => array(
                                'description' => 'The time when the workflow execution was closed. Set only if the execution status is CLOSED.',
                                'type' => 'string',
                            ),
                            'executionStatus' => array(
                                'description' => 'The current status of the execution.',
                                'type' => 'string',
                            ),
                            'closeStatus' => array(
                                'description' => 'If the execution status is closed then this specifies how the execution was closed:',
                                'type' => 'string',
                            ),
                            'parent' => array(
                                'description' => 'If this workflow execution is a child of another execution then contains the workflow execution that started this execution.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The user defined identifier associated with the workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'A system generated unique identifier for the workflow execution.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'tagList' => array(
                                'description' => 'The list of tags associated with the workflow execution. Tags can be used to identify and list workflow executions of interest through the visibility APIs. A workflow execution can have a maximum of 5 tags.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Tag',
                                    'type' => 'string',
                                ),
                            ),
                            'cancelRequested' => array(
                                'description' => 'Set to true if a cancellation is requested for this workflow execution.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'The token of the next page in the result. If set, the results have more than one page. The next page can be retrieved by repeating the request with this token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DomainInfos' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'domainInfos' => array(
                    'description' => 'A list of DomainInfo structures.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'DomainInfo',
                        'description' => 'Contains general information about a domain.',
                        'type' => 'object',
                        'properties' => array(
                            'name' => array(
                                'description' => 'The name of the domain. This name is unique within the account.',
                                'type' => 'string',
                            ),
                            'status' => array(
                                'description' => 'The status of the domain:',
                                'type' => 'string',
                            ),
                            'description' => array(
                                'description' => 'The description of the domain provided through RegisterDomain.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'Returns a value if the results are paginated. To get the next page of results, repeat the request specifying this token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'WorkflowTypeInfos' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'typeInfos' => array(
                    'description' => 'The list of workflow type information.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'WorkflowTypeInfo',
                        'description' => 'Contains information about a workflow type.',
                        'type' => 'object',
                        'properties' => array(
                            'workflowType' => array(
                                'description' => 'The workflow type this information is about.',
                                'type' => 'object',
                                'properties' => array(
                                    'name' => array(
                                        'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                        'type' => 'string',
                                    ),
                                    'version' => array(
                                        'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'status' => array(
                                'description' => 'The current status of the workflow type.',
                                'type' => 'string',
                            ),
                            'description' => array(
                                'description' => 'The description of the type registered through RegisterWorkflowType.',
                                'type' => 'string',
                            ),
                            'creationDate' => array(
                                'description' => 'The date when this type was registered.',
                                'type' => 'string',
                            ),
                            'deprecationDate' => array(
                                'description' => 'If the type is in deprecated state, then it is set to the date when the type was deprecated.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'The token for the next page of type information. If set then the list consists of more than one page. You can retrieve the next page by repeating the request (that returned the structure) with the this token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ActivityTask' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'taskToken' => array(
                    'description' => 'The opaque string used as a handle on the task. This token is used by workers to communicate progress and response information back to the system about the task.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'activityId' => array(
                    'description' => 'The unique ID of the task.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'startedEventId' => array(
                    'description' => 'The id of the ActivityTaskStarted event recorded in the history.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'workflowExecution' => array(
                    'description' => 'The workflow execution that started this activity task.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowId' => array(
                            'description' => 'The user defined identifier associated with the workflow execution.',
                            'type' => 'string',
                        ),
                        'runId' => array(
                            'description' => 'A system generated unique identifier for the workflow execution.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'activityType' => array(
                    'description' => 'The type of this activity task.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                            'type' => 'string',
                        ),
                        'version' => array(
                            'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'input' => array(
                    'description' => 'The inputs provided when the activity task was scheduled. The form of the input is user defined and should be meaningful to the activity implementation.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DecisionTask' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'taskToken' => array(
                    'description' => 'The opaque string used as a handle on the task. This token is used by workers to communicate progress and response information back to the system about the task.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'startedEventId' => array(
                    'description' => 'The id of the DecisionTaskStarted event recorded in the history.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'workflowExecution' => array(
                    'description' => 'The workflow execution for which this decision task was created.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'workflowId' => array(
                            'description' => 'The user defined identifier associated with the workflow execution.',
                            'type' => 'string',
                        ),
                        'runId' => array(
                            'description' => 'A system generated unique identifier for the workflow execution.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'workflowType' => array(
                    'description' => 'The type of the workflow execution for which this decision task was created.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'name' => array(
                            'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                            'type' => 'string',
                        ),
                        'version' => array(
                            'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'events' => array(
                    'description' => 'A paginated list of history events of the workflow execution. The decider uses this during the processing of the decision task.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'HistoryEvent',
                        'description' => 'Event within a workflow execution. A history event can be one of these types:',
                        'type' => 'object',
                        'properties' => array(
                            'eventTimestamp' => array(
                                'description' => 'The date and time when the event occurred.',
                                'type' => 'string',
                            ),
                            'eventType' => array(
                                'description' => 'The type of the history event.',
                                'type' => 'string',
                            ),
                            'eventId' => array(
                                'description' => 'The system generated id of the event. This id uniquely identifies the event with in the workflow execution history.',
                                'type' => 'numeric',
                            ),
                            'workflowExecutionStartedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'input' => array(
                                        'description' => 'The input provided to the workflow execution (if any).',
                                        'type' => 'string',
                                    ),
                                    'executionStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration for this workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'taskStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration of decision tasks for this workflow type.',
                                        'type' => 'string',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy to use for the child workflow executions if this workflow execution is terminated, by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout. The supported child policies are: TERMINATE: the child executions will be terminated. REQUEST_CANCEL: a request to cancel will be attempted for each child execution by recording a WorkflowExecutionCancelRequested event in its history. It is up to the decider to take appropriate actions when it receives an execution history with this event. ABANDON: no action will be taken. The child executions will continue to run.',
                                        'type' => 'string',
                                    ),
                                    'taskList' => array(
                                        'description' => 'The name of the task list for scheduling the decision tasks for this workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The workflow type of this execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'tagList' => array(
                                        'description' => 'The list of tags associated with this workflow execution. An execution can have up to 5 tags.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Tag',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'continuedExecutionRunId' => array(
                                        'description' => 'If this workflow execution was started due to a ContinueAsNewWorkflowExecution decision, then it contains the runId of the previous workflow execution that was closed and continued as this execution.',
                                        'type' => 'string',
                                    ),
                                    'parentWorkflowExecution' => array(
                                        'description' => 'The source workflow execution that started this workflow execution. The member is not set if the workflow execution was not started by a workflow.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'parentInitiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this workflow execution. The source event with this Id can be found in the history of the source workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionCompletedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionCompleted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'result' => array(
                                        'description' => 'The result produced by the workflow execution upon successful completion.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CompleteWorkflowExecution decision to complete this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'completeWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type CompleteWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'cause' => array(
                                        'description' => 'The cause of the failure. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CompleteWorkflowExecution decision to complete this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'reason' => array(
                                        'description' => 'The descriptive reason provided for the failure (if any).',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'The details of the failure (if any).',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the FailWorkflowExecution decision to fail this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'failWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type FailWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'cause' => array(
                                        'description' => 'The cause of the failure. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the FailWorkflowExecution decision to fail this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionTimedOutEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionTimedOut then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timeoutType' => array(
                                        'description' => 'The type of timeout that caused this event.',
                                        'type' => 'string',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy used for the child workflow executions of this workflow execution. The supported child policies are: TERMINATE: the child executions will be terminated. REQUEST_CANCEL: a request to cancel will be attempted for each child execution by recording a WorkflowExecutionCancelRequested event in its history. It is up to the decider to take appropriate actions when it receives an execution history with this event. ABANDON: no action will be taken. The child executions will continue to run.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'workflowExecutionCanceledEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionCanceled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'details' => array(
                                        'description' => 'Details for the cancellation (if any).',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CancelWorkflowExecution decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'cancelWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type CancelWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'cause' => array(
                                        'description' => 'The cause of the failure. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CancelWorkflowExecution decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionContinuedAsNewEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionContinuedAsNew then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'input' => array(
                                        'description' => 'The input provided to the new workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the ContinueAsNewWorkflowExecution decision that started this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'newExecutionRunId' => array(
                                        'description' => 'The runId of the new workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'executionStartToCloseTimeout' => array(
                                        'description' => 'The total duration allowed for the new workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'taskList' => array(
                                        'description' => 'Represents a task list.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'taskStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration of decision tasks for the new workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy to use for the child workflow executions of the new execution if it is terminated by calling the TerminateWorkflowExecution action explicitly or due to an expired timeout.',
                                        'type' => 'string',
                                    ),
                                    'tagList' => array(
                                        'description' => 'The list of tags associated with the new workflow execution.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Tag',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'Represents a workflow type.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'continueAsNewWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type ContinueAsNewWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'cause' => array(
                                        'description' => 'The cause of the failure. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the ContinueAsNewWorkflowExecution decision that started this execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'workflowExecutionTerminatedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionTerminated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'reason' => array(
                                        'description' => 'The reason provided for the termination (if any).',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'The details provided for the termination (if any).',
                                        'type' => 'string',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy used for the child workflow executions of this workflow execution. The supported child policies are:',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'If set, indicates that the workflow execution was automatically terminated, and specifies the cause. This happens if the parent workflow execution times out or is terminated and the child policy is set to terminate child executions.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'workflowExecutionCancelRequestedEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionCancelRequested then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'externalWorkflowExecution' => array(
                                        'description' => 'The external workflow execution for which the cancellation was requested.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'externalInitiatedEventId' => array(
                                        'description' => 'The id of the RequestCancelExternalWorkflowExecutionInitiated event corresponding to the RequestCancelExternalWorkflowExecution decision to cancel this workflow execution.The source event with this Id can be found in the history of the source workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'cause' => array(
                                        'description' => 'If set, indicates that the request to cancel the workflow execution was automatically generated, and specifies the cause. This happens if the parent workflow execution times out or is terminated, and the child policy is set to cancel child executions.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'decisionTaskScheduledEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskScheduled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'taskList' => array(
                                        'description' => 'The name of the task list in which the decision task was scheduled.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'startToCloseTimeout' => array(
                                        'description' => 'The maximum duration for this decision task. The task is considered timed out if it does not completed within this duration.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'decisionTaskStartedEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'identity' => array(
                                        'description' => 'Identity of the decider making the request. This enables diagnostic tracing when problems arise. The form of this identity is user defined.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the DecisionTaskScheduled event that was recorded when this decision task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'decisionTaskCompletedEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskCompleted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'executionContext' => array(
                                        'description' => 'User defined context for the workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the DecisionTaskScheduled event that was recorded when this decision task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the DecisionTaskStarted event recorded when this decision task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'decisionTaskTimedOutEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskTimedOut then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timeoutType' => array(
                                        'description' => 'The type of timeout that expired before the decision task could be completed.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the DecisionTaskScheduled event that was recorded when this decision task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the DecisionTaskStarted event recorded when this decision task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskScheduledEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskScheduled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'activityType' => array(
                                        'description' => 'The type of the activity task.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'activityId' => array(
                                        'description' => 'The unique id of the activity task.',
                                        'type' => 'string',
                                    ),
                                    'input' => array(
                                        'description' => 'The input provided to the activity task.',
                                        'type' => 'string',
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks. This data is not sent to the activity.',
                                        'type' => 'string',
                                    ),
                                    'scheduleToStartTimeout' => array(
                                        'description' => 'The maximum amount of time the activity task can wait to be assigned to a worker.',
                                        'type' => 'string',
                                    ),
                                    'scheduleToCloseTimeout' => array(
                                        'description' => 'The maximum amount of time for this activity task.',
                                        'type' => 'string',
                                    ),
                                    'startToCloseTimeout' => array(
                                        'description' => 'The maximum amount of time a worker may take to process the activity task.',
                                        'type' => 'string',
                                    ),
                                    'taskList' => array(
                                        'description' => 'The task list in which the activity task has been scheduled.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision that resulted in the scheduling of this activity task. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'heartbeatTimeout' => array(
                                        'description' => 'The maximum time before which the worker processing this task must report progress by calling RecordActivityTaskHeartbeat. If the timeout is exceeded, the activity task is automatically timed out. If the worker subsequently attempts to record a heartbeat or return a result, it will be ignored.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'activityTaskStartedEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'identity' => array(
                                        'description' => 'Identity of the worker that was assigned this task. This aids diagnostics when problems arise. The form of this identity is user defined.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskCompletedEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskCompleted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'result' => array(
                                        'description' => 'The results of the activity task (if any).',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ActivityTaskStarted event recorded when this activity task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskFailedEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'reason' => array(
                                        'description' => 'The reason provided for the failure (if any).',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'The details of the failure (if any).',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ActivityTaskStarted event recorded when this activity task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskTimedOutEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskTimedOut then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timeoutType' => array(
                                        'description' => 'The type of the timeout that caused this event.',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ActivityTaskStarted event recorded when this activity task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'details' => array(
                                        'description' => 'Contains the content of the details parameter for the last call made by the activity to RecordActivityTaskHeartbeat.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'activityTaskCanceledEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskCanceled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'details' => array(
                                        'description' => 'Details of the cancellation (if any).',
                                        'type' => 'string',
                                    ),
                                    'scheduledEventId' => array(
                                        'description' => 'The id of the ActivityTaskScheduled event that was recorded when this activity task was scheduled. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ActivityTaskStarted event recorded when this activity task was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'latestCancelRequestedEventId' => array(
                                        'description' => 'If set, contains the Id of the last ActivityTaskCancelRequested event recorded for this activity task. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'activityTaskCancelRequestedEventAttributes' => array(
                                'description' => 'If the event is of type ActivityTaskcancelRequested then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RequestCancelActivityTask decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'activityId' => array(
                                        'description' => 'The unique ID of the task.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'workflowExecutionSignaledEventAttributes' => array(
                                'description' => 'If the event is of type WorkflowExecutionSignaled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'signalName' => array(
                                        'description' => 'The name of the signal received. The decider can use the signal name and inputs to determine how to the process the signal.',
                                        'type' => 'string',
                                    ),
                                    'input' => array(
                                        'description' => 'Inputs provided with the signal (if any). The decider can use the signal name and inputs to determine how to process the signal.',
                                        'type' => 'string',
                                    ),
                                    'externalWorkflowExecution' => array(
                                        'description' => 'The workflow execution that sent the signal. This is set only of the signal was sent by another workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'externalInitiatedEventId' => array(
                                        'description' => 'The id of the SignalExternalWorkflowExecutionInitiated event corresponding to the SignalExternalWorkflow decision to signal this workflow execution.The source event with this Id can be found in the history of the source workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event. This field is set only if the signal was initiated by another workflow execution.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'markerRecordedEventAttributes' => array(
                                'description' => 'If the event is of type MarkerRecorded then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'markerName' => array(
                                        'description' => 'The name of the marker.',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'Details of the marker (if any).',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RecordMarker decision that requested this marker. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'recordMarkerFailedEventAttributes' => array(
                                'description' => 'If the event is of type DecisionTaskFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'markerName' => array(
                                        'description' => 'The marker\'s name.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RecordMarkerFailed decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'timerStartedEventAttributes' => array(
                                'description' => 'If the event is of type TimerStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The unique Id of the timer that was started.',
                                        'type' => 'string',
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks.',
                                        'type' => 'string',
                                    ),
                                    'startToFireTimeout' => array(
                                        'description' => 'The duration of time after which the timer will fire.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the StartTimer decision for this activity task. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'timerFiredEventAttributes' => array(
                                'description' => 'If the event is of type TimerFired then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The unique Id of the timer that fired.',
                                        'type' => 'string',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The id of the TimerStarted event that was recorded when this timer was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'timerCanceledEventAttributes' => array(
                                'description' => 'If the event is of type TimerCanceled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The unique Id of the timer that was canceled.',
                                        'type' => 'string',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The id of the TimerStarted event that was recorded when this timer was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CancelTimer decision to cancel this timer. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'startChildWorkflowExecutionInitiatedEventAttributes' => array(
                                'description' => 'If the event is of type StartChildWorkflowExecutionInitiated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the child workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent decision tasks. This data is not sent to the activity.',
                                        'type' => 'string',
                                    ),
                                    'input' => array(
                                        'description' => 'The inputs provided to the child workflow execution (if any).',
                                        'type' => 'string',
                                    ),
                                    'executionStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration for the child workflow execution. If the workflow execution is not closed within this duration, it will be timed out and force terminated.',
                                        'type' => 'string',
                                    ),
                                    'taskList' => array(
                                        'description' => 'The name of the task list used for the decision tasks of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the task list.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the StartChildWorkflowExecution Decision to request this child workflow execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'childPolicy' => array(
                                        'description' => 'The policy to use for the child workflow executions if this execution gets terminated by explicitly calling the TerminateWorkflowExecution action or due to an expired timeout.',
                                        'type' => 'string',
                                    ),
                                    'taskStartToCloseTimeout' => array(
                                        'description' => 'The maximum duration allowed for the decision tasks for this workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'tagList' => array(
                                        'description' => 'The list of tags to associated with the child workflow execution.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Tag',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionStartedEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionStarted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that was started.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionCompletedEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionCompleted then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that was completed.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'result' => array(
                                        'description' => 'The result of the child workflow execution (if any).',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that failed.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'reason' => array(
                                        'description' => 'The reason for the failure (if provided).',
                                        'type' => 'string',
                                    ),
                                    'details' => array(
                                        'description' => 'The details of the failure (if provided).',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionTimedOutEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionTimedOut then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that timed out.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'timeoutType' => array(
                                        'description' => 'The type of the timeout that caused the child workflow execution to time out.',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionCanceledEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionCanceled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that was canceled.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'details' => array(
                                        'description' => 'Details of the cancellation (if provided).',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'childWorkflowExecutionTerminatedEventAttributes' => array(
                                'description' => 'If the event is of type ChildWorkflowExecutionTerminated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The child workflow execution that was terminated.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'workflowType' => array(
                                        'description' => 'The type of the child workflow execution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'startedEventId' => array(
                                        'description' => 'The Id of the ChildWorkflowExecutionStarted event recorded when this child workflow execution was started. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'signalExternalWorkflowExecutionInitiatedEventAttributes' => array(
                                'description' => 'If the event is of type SignalExternalWorkflowExecutionInitiated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the external workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution to send the signal to.',
                                        'type' => 'string',
                                    ),
                                    'signalName' => array(
                                        'description' => 'The name of the signal.',
                                        'type' => 'string',
                                    ),
                                    'input' => array(
                                        'description' => 'Input provided to the signal (if any).',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the SignalExternalWorkflowExecution decision for this signal. This information can be useful for diagnosing problems by tracing back the cause of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent decision tasks.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'externalWorkflowExecutionSignaledEventAttributes' => array(
                                'description' => 'If the event is of type ExternalWorkflowExecutionSignaled then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The external workflow execution that the signal was delivered to.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the SignalExternalWorkflowExecutionInitiated event corresponding to the SignalExternalWorkflowExecution decision to request this signal. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'signalExternalWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type SignalExternalWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the external workflow execution that the signal was being delivered to.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution that the signal was being delivered to.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the SignalExternalWorkflowExecutionInitiated event corresponding to the SignalExternalWorkflowExecution decision to request this signal. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the SignalExternalWorkflowExecution decision for this signal. This information can be useful for diagnosing problems by tracing back the cause of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'externalWorkflowExecutionCancelRequestedEventAttributes' => array(
                                'description' => 'If the event is of type ExternalWorkflowExecutionCancelRequested then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowExecution' => array(
                                        'description' => 'The external workflow execution to which the cancellation request was delivered.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'workflowId' => array(
                                                'description' => 'The user defined identifier associated with the workflow execution.',
                                                'type' => 'string',
                                            ),
                                            'runId' => array(
                                                'description' => 'A system generated unique identifier for the workflow execution.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the RequestCancelExternalWorkflowExecutionInitiated event corresponding to the RequestCancelExternalWorkflowExecution decision to cancel this external workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'requestCancelExternalWorkflowExecutionInitiatedEventAttributes' => array(
                                'description' => 'If the event is of type RequestCancelExternalWorkflowExecutionInitiated then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the external workflow execution to be canceled.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution to be canceled.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RequestCancelExternalWorkflowExecution decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'description' => 'Optional data attached to the event that can be used by the decider in subsequent workflow tasks.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'requestCancelExternalWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type RequestCancelExternalWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the external workflow to which the cancel request was to be delivered.',
                                        'type' => 'string',
                                    ),
                                    'runId' => array(
                                        'description' => 'The runId of the external workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the RequestCancelExternalWorkflowExecutionInitiated event corresponding to the RequestCancelExternalWorkflowExecution decision to cancel this external workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RequestCancelExternalWorkflowExecution decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'scheduleActivityTaskFailedEventAttributes' => array(
                                'description' => 'If the event is of type ScheduleActivityTaskFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'activityType' => array(
                                        'description' => 'The activity type provided in the ScheduleActivityTask decision that failed.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of this activity. The combination of activity type name and version must be unique within a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of this activity. The combination of activity type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'activityId' => array(
                                        'description' => 'The activityId provided in the ScheduleActivityTask decision that failed.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision that resulted in the scheduling of this activity task. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'requestCancelActivityTaskFailedEventAttributes' => array(
                                'description' => 'If the event is of type RequestCancelActivityTaskFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'activityId' => array(
                                        'description' => 'The activityId provided in the RequestCancelActivityTask decision that failed.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the RequestCancelActivityTask decision for this cancellation request. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'startTimerFailedEventAttributes' => array(
                                'description' => 'If the event is of type StartTimerFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The timerId provided in the StartTimer decision that failed.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the StartTimer decision for this activity task. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'cancelTimerFailedEventAttributes' => array(
                                'description' => 'If the event is of type CancelTimerFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'timerId' => array(
                                        'description' => 'The timerId provided in the CancelTimer decision that failed.',
                                        'type' => 'string',
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the CancelTimer decision to cancel this timer. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'startChildWorkflowExecutionFailedEventAttributes' => array(
                                'description' => 'If the event is of type StartChildWorkflowExecutionFailed then this member is set and provides detailed information about the event. It is not set for other event types.',
                                'type' => 'object',
                                'properties' => array(
                                    'workflowType' => array(
                                        'description' => 'The workflow type provided in the StartChildWorkflowExecution Decision that failed.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'name' => array(
                                                'description' => 'The name of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                            'version' => array(
                                                'description' => 'The version of the workflow type. This field is required. The combination of workflow type name and version must be unique with in a domain.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'cause' => array(
                                        'description' => 'The cause of the failure to process the decision. This information is generated by the system and can be useful for diagnostic purposes.',
                                        'type' => 'string',
                                    ),
                                    'workflowId' => array(
                                        'description' => 'The workflowId of the child workflow execution.',
                                        'type' => 'string',
                                    ),
                                    'initiatedEventId' => array(
                                        'description' => 'The id of the StartChildWorkflowExecutionInitiated event corresponding to the StartChildWorkflowExecution Decision to start this child workflow execution. This information can be useful for diagnosing problems by tracing back the chain of events leading up to this event.',
                                        'type' => 'numeric',
                                    ),
                                    'decisionTaskCompletedEventId' => array(
                                        'description' => 'The id of the DecisionTaskCompleted event corresponding to the decision task that resulted in the StartChildWorkflowExecution Decision to request this child workflow execution. This information can be useful for diagnosing problems by tracing back the cause of events.',
                                        'type' => 'numeric',
                                    ),
                                    'control' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'nextPageToken' => array(
                    'description' => 'Returns a value if the results are paginated. To get the next page of results, repeat the request specifying this token and all other arguments unchanged.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'previousStartedEventId' => array(
                    'description' => 'The id of the DecisionTaskStarted event of the previous decision task of this workflow execution that was processed by the decider. This can be used to determine the events in the history new since the last decision task received by the decider.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'ActivityTaskStatus' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'cancelRequested' => array(
                    'description' => 'Set to true if cancellation of the task is requested.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'Run' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'runId' => array(
                    'description' => 'The runId of a workflow execution. This Id is generated by the service and can be used to uniquely identify the workflow execution within a domain.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'GetWorkflowExecutionHistory' => array(
                'token_param' => 'nextPageToken',
                'token_key' => 'nextPageToken',
                'limit_key' => 'maximumPageSize',
                'result_key' => 'events',
            ),
            'ListActivityTypes' => array(
                'token_param' => 'nextPageToken',
                'token_key' => 'nextPageToken',
                'limit_key' => 'maximumPageSize',
                'result_key' => 'typeInfos',
            ),
            'ListClosedWorkflowExecutions' => array(
                'token_param' => 'nextPageToken',
                'token_key' => 'nextPageToken',
                'limit_key' => 'maximumPageSize',
                'result_key' => 'executionInfos',
            ),
            'ListDomains' => array(
                'token_param' => 'nextPageToken',
                'token_key' => 'nextPageToken',
                'limit_key' => 'maximumPageSize',
                'result_key' => 'domainInfos',
            ),
            'ListOpenWorkflowExecutions' => array(
                'token_param' => 'nextPageToken',
                'token_key' => 'nextPageToken',
                'limit_key' => 'maximumPageSize',
                'result_key' => 'executionInfos',
            ),
            'ListWorkflowTypes' => array(
                'token_param' => 'nextPageToken',
                'token_key' => 'nextPageToken',
                'limit_key' => 'maximumPageSize',
                'result_key' => 'typeInfos',
            ),
            'PollForDecisionTask' => array(
                'token_param' => 'nextPageToken',
                'token_key' => 'nextPageToken',
                'limit_key' => 'maximumPageSize',
                'result_key' => 'events',
            ),
        ),
    ),
);
