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
    'apiVersion' => '2012-10-29',
    'endpointPrefix' => 'datapipeline',
    'serviceFullName' => 'AWS Data Pipeline',
    'serviceType' => 'json',
    'jsonVersion' => '1.1',
    'targetPrefix' => 'DataPipeline.',
    'signatureVersion' => 'v4',
    'namespace' => 'DataPipeline',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'datapipeline.us-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'ActivatePipeline' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Validates a pipeline and initiates processing. If the pipeline does not pass validation, activation fails.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.ActivatePipeline',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline to activate.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
            ),
        ),
        'CreatePipeline' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreatePipelineOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a new empty pipeline. When this action succeeds, you can then use the PutPipelineDefinition action to populate the pipeline.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.CreatePipeline',
                ),
                'name' => array(
                    'required' => true,
                    'description' => 'The name of the new pipeline. You can use the same name for multiple pipelines associated with your AWS account, because AWS Data Pipeline assigns each new pipeline a unique pipeline identifier.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'uniqueId' => array(
                    'required' => true,
                    'description' => 'A unique identifier that you specify. This identifier is not the same as the pipeline identifier assigned by AWS Data Pipeline. You are responsible for defining the format and ensuring the uniqueness of this identifier. You use this parameter to ensure idempotency during repeated calls to CreatePipeline. For example, if the first call to CreatePipeline does not return a clear success, you can pass in the same unique identifier and pipeline name combination on a subsequent call to CreatePipeline. CreatePipeline ensures that if a pipeline already exists with the same name and unique identifier, a new pipeline will not be created. Instead, you\'ll receive the pipeline identifier from the previous attempt. The uniqueness of the name and unique identifier combination is scoped to the AWS account or IAM user credentials.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'description' => array(
                    'description' => 'The description of the new pipeline.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
            ),
        ),
        'DeletePipeline' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Permanently deletes a pipeline, its pipeline definition and its run history. You cannot query or restore a deleted pipeline. AWS Data Pipeline will attempt to cancel instances associated with the pipeline that are currently being processed by task runners. Deleting a pipeline cannot be undone.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.DeletePipeline',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline to be deleted.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
            ),
        ),
        'DescribeObjects' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeObjectsOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the object definitions for a set of objects associated with the pipeline. Object definitions are composed of a set of fields that define the properties of the object.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.DescribeObjects',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'Identifier of the pipeline that contains the object definitions.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'objectIds' => array(
                    'required' => true,
                    'description' => 'Identifiers of the pipeline objects that contain the definitions to be described. You can pass as many as 25 identifiers in a single call to DescribeObjects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'id',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1024,
                    ),
                ),
                'evaluateExpressions' => array(
                    'description' => 'Indicates whether any expressions in the object should be evaluated when the object descriptions are returned.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'marker' => array(
                    'description' => 'The starting point for the results to be returned. The first time you call DescribeObjects, this value should be empty. As long as the action returns HasMoreResults as True, you can call DescribeObjects again and pass the marker value from the response to retrieve the next set of results.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
            ),
        ),
        'DescribePipelines' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribePipelinesOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Retrieve metadata about one or more pipelines. The information retrieved includes the name of the pipeline, the pipeline identifier, its current state, and the user account that owns the pipeline. Using account credentials, you can retrieve metadata about pipelines that you or your IAM users have created. If you are using an IAM user account, you can retrieve metadata about only those pipelines you have read permission for.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.DescribePipelines',
                ),
                'pipelineIds' => array(
                    'required' => true,
                    'description' => 'Identifiers of the pipelines to describe. You can pass as many as 25 identifiers in a single call to DescribePipelines. You can obtain pipeline identifiers by calling ListPipelines.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'id',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1024,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
            ),
        ),
        'EvaluateExpression' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EvaluateExpressionOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Evaluates a string in the context of a specified object. A task runner can use this action to evaluate SQL queries stored in Amazon S3.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.EvaluateExpression',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'objectId' => array(
                    'required' => true,
                    'description' => 'The identifier of the object.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'expression' => array(
                    'required' => true,
                    'description' => 'The expression to evaluate.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 20971520,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The specified task was not found.',
                    'class' => 'TaskNotFoundException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
            ),
        ),
        'GetPipelineDefinition' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'GetPipelineDefinitionOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns the definition of the specified pipeline. You can call GetPipelineDefinition to retrieve the pipeline definition you provided using PutPipelineDefinition.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.GetPipelineDefinition',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'version' => array(
                    'description' => 'The version of the pipeline definition to retrieve. This parameter accepts the values latest (default) and active. Where latest indicates the last definition saved to the pipeline and active indicates the last definition of the pipeline that was activated.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
            ),
        ),
        'ListPipelines' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ListPipelinesOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns a list of pipeline identifiers for all active pipelines. Identifiers are returned only for pipelines you have permission to access.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.ListPipelines',
                ),
                'marker' => array(
                    'description' => 'The starting point for the results to be returned. The first time you call ListPipelines, this value should be empty. As long as the action returns HasMoreResults as True, you can call ListPipelines again and pass the marker value from the response to retrieve the next set of results.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
            ),
        ),
        'PollForTask' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'PollForTaskOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Task runners call this action to receive a task to perform from AWS Data Pipeline. The task runner specifies which tasks it can perform by setting a value for the workerGroup parameter of the PollForTask call. The task returned by PollForTask may come from any of the pipelines that match the workerGroup value passed in by the task runner and that was launched using the IAM user credentials specified by the task runner.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.PollForTask',
                ),
                'workerGroup' => array(
                    'required' => true,
                    'description' => 'Indicates the type of task the task runner is configured to accept and process. The worker group is set as a field on objects in the pipeline when they are created. You can only specify a single value for workerGroup in the call to PollForTask. There are no wildcard values permitted in workerGroup, the string must be an exact, case-sensitive, match.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
                'hostname' => array(
                    'description' => 'The public DNS name of the calling task runner.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'instanceIdentity' => array(
                    'description' => 'Identity information for the Amazon EC2 instance that is hosting the task runner. You can get this value by calling the URI, http://169.254.169.254/latest/meta-data/instance-id, from the EC2 instance. For more information, go to Instance Metadata in the Amazon Elastic Compute Cloud User Guide. Passing in this value proves that your task runner is running on an EC2 instance, and ensures the proper AWS Data Pipeline service charges are applied to your pipeline.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'document' => array(
                            'description' => 'A description of an Amazon EC2 instance that is generated when the instance is launched and exposed to the instance via the instance metadata service in the form of a JSON representation of an object.',
                            'type' => 'string',
                            'maxLength' => 1024,
                        ),
                        'signature' => array(
                            'description' => 'A signature which can be used to verify the accuracy and authenticity of the information provided in the instance identity document.',
                            'type' => 'string',
                            'maxLength' => 1024,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
                array(
                    'reason' => 'The specified task was not found.',
                    'class' => 'TaskNotFoundException',
                ),
            ),
        ),
        'PutPipelineDefinition' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'PutPipelineDefinitionOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Adds tasks, schedules, and preconditions that control the behavior of the pipeline. You can use PutPipelineDefinition to populate a new pipeline or to update an existing pipeline that has not yet been activated.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.PutPipelineDefinition',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'The identifier of the pipeline to be configured.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'pipelineObjects' => array(
                    'required' => true,
                    'description' => 'The objects that define the pipeline. These will overwrite the existing pipeline definition.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'PipelineObject',
                        'description' => 'Contains information about a pipeline object. This can be a logical, physical, or physical attempt pipeline object. The complete set of components of a pipeline defines the pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'required' => true,
                                'description' => 'Identifier of the object.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 1024,
                            ),
                            'name' => array(
                                'required' => true,
                                'description' => 'Name of the object.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 1024,
                            ),
                            'fields' => array(
                                'required' => true,
                                'description' => 'Key-value pairs that define the properties of the object.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Field',
                                    'description' => 'A key-value pair that describes a property of a pipeline object. The value is specified as either a string value (StringValue) or a reference to another object (RefValue) but not as both.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'key' => array(
                                            'required' => true,
                                            'description' => 'The field identifier.',
                                            'type' => 'string',
                                            'minLength' => 1,
                                            'maxLength' => 256,
                                        ),
                                        'stringValue' => array(
                                            'description' => 'The field value, expressed as a String.',
                                            'type' => 'string',
                                            'maxLength' => 10240,
                                        ),
                                        'refValue' => array(
                                            'description' => 'The field value, expressed as the identifier of another object.',
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
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
            ),
        ),
        'QueryObjects' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'QueryObjectsOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Queries a pipeline for the names of objects that match a specified set of conditions.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.QueryObjects',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'Identifier of the pipeline to be queried for object names.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'query' => array(
                    'description' => 'Query that defines the objects to be returned. The Query object can contain a maximum of ten selectors. The conditions in the query are limited to top-level String fields in the object. These filters can be applied to components, instances, and attempts.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'selectors' => array(
                            'description' => 'List of selectors that define the query. An object must satisfy all of the selectors to match the query.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Selector',
                                'description' => 'A comparision that is used to determine whether a query should return this object.',
                                'type' => 'object',
                                'properties' => array(
                                    'fieldName' => array(
                                        'description' => 'The name of the field that the operator will be applied to. The field name is the "key" portion of the field definition in the pipeline definition syntax that is used by the AWS Data Pipeline API. If the field is not set on the object, the condition fails.',
                                        'type' => 'string',
                                        'maxLength' => 1024,
                                    ),
                                    'operator' => array(
                                        'description' => 'Contains a logical operation for comparing the value of a field with a specified value.',
                                        'type' => 'object',
                                        'properties' => array(
                                            '' => array(
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'sphere' => array(
                    'required' => true,
                    'description' => 'Specifies whether the query applies to components or instances. Allowable values: COMPONENT, INSTANCE, ATTEMPT.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
                'marker' => array(
                    'description' => 'The starting point for the results to be returned. The first time you call QueryObjects, this value should be empty. As long as the action returns HasMoreResults as True, you can call QueryObjects again and pass the marker value from the response to retrieve the next set of results.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
                'limit' => array(
                    'description' => 'Specifies the maximum number of object names that QueryObjects will return in a single call. The default value is 100.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
            ),
        ),
        'ReportTaskProgress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ReportTaskProgressOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Updates the AWS Data Pipeline service on the progress of the calling task runner. When the task runner is assigned a task, it should call ReportTaskProgress to acknowledge that it has the task within 2 minutes. If the web service does not recieve this acknowledgement within the 2 minute window, it will assign the task in a subsequent PollForTask call. After this initial acknowledgement, the task runner only needs to report progress every 15 minutes to maintain its ownership of the task. You can change this reporting time from 15 minutes by specifying a reportProgressTimeout field in your pipeline. If a task runner does not report its status after 5 minutes, AWS Data Pipeline will assume that the task runner is unable to process the task and will reassign the task in a subsequent response to PollForTask. task runners should call ReportTaskProgress every 60 seconds.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.ReportTaskProgress',
                ),
                'taskId' => array(
                    'required' => true,
                    'description' => 'Identifier of the task assigned to the task runner. This value is provided in the TaskObject that the service returns with the response for the PollForTask action.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 2048,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
                array(
                    'reason' => 'The specified task was not found.',
                    'class' => 'TaskNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
            ),
        ),
        'ReportTaskRunnerHeartbeat' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ReportTaskRunnerHeartbeatOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Task runners call ReportTaskRunnerHeartbeat every 15 minutes to indicate that they are operational. In the case of AWS Data Pipeline Task Runner launched on a resource managed by AWS Data Pipeline, the web service can use this call to detect when the task runner application has failed and restart a new instance.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.ReportTaskRunnerHeartbeat',
                ),
                'taskrunnerId' => array(
                    'required' => true,
                    'description' => 'The identifier of the task runner. This value should be unique across your AWS account. In the case of AWS Data Pipeline Task Runner launched on a resource managed by AWS Data Pipeline, the web service provides a unique identifier when it launches the application. If you have written a custom task runner, you should assign a unique identifier for the task runner.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'workerGroup' => array(
                    'description' => 'Indicates the type of task the task runner is configured to accept and process. The worker group is set as a field on objects in the pipeline when they are created. You can only specify a single value for workerGroup in the call to ReportTaskRunnerHeartbeat. There are no wildcard values permitted in workerGroup, the string must be an exact, case-sensitive, match.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
                'hostname' => array(
                    'description' => 'The public DNS name of the calling task runner.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
            ),
        ),
        'SetStatus' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Requests that the status of an array of physical or logical pipeline objects be updated in the pipeline. This update may not occur immediately, but is eventually consistent. The status that can be set depends on the type of object.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.SetStatus',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'Identifies the pipeline that contains the objects.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'objectIds' => array(
                    'required' => true,
                    'description' => 'Identifies an array of objects. The corresponding objects can be either physical or components, but not a mix of both types.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'id',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1024,
                    ),
                ),
                'status' => array(
                    'required' => true,
                    'description' => 'Specifies the status to be set on all the objects in objectIds. For components, this can be either PAUSE or RESUME. For instances, this can be either CANCEL, RERUN, or MARK_FINISHED.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
            ),
        ),
        'SetTaskStatus' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Notifies AWS Data Pipeline that a task is completed and provides information about the final status. The task runner calls this action regardless of whether the task was sucessful. The task runner does not need to call SetTaskStatus for tasks that are canceled by the web service during a call to ReportTaskProgress.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.SetTaskStatus',
                ),
                'taskId' => array(
                    'required' => true,
                    'description' => 'Identifies the task assigned to the task runner. This value is set in the TaskObject that is returned by the PollForTask action.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 2048,
                ),
                'taskStatus' => array(
                    'required' => true,
                    'description' => 'If FINISHED, the task successfully completed. If FAILED the task ended unsuccessfully. The FALSE value is used by preconditions.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'FINISHED',
                        'FAILED',
                        'FALSE',
                    ),
                ),
                'errorId' => array(
                    'description' => 'If an error occurred during the task, this value specifies an id value that represents the error. This value is set on the physical attempt object. It is used to display error information to the user. It should not start with string "Service_" which is reserved by the system.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
                'errorMessage' => array(
                    'description' => 'If an error occurred during the task, this value specifies a text description of the error. This value is set on the physical attempt object. It is used to display error information to the user. The web service does not parse this value.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'errorStackTrace' => array(
                    'description' => 'If an error occurred during the task, this value specifies the stack trace associated with the error. This value is set on the physical attempt object. It is used to display error information to the user. The web service does not parse this value.',
                    'type' => 'string',
                    'location' => 'json',
                    'maxLength' => 1024,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The specified task was not found.',
                    'class' => 'TaskNotFoundException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
            ),
        ),
        'ValidatePipelineDefinition' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ValidatePipelineDefinitionOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Tests the pipeline definition with a set of validation checks to ensure that it is well formed and can run without error.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'DataPipeline.ValidatePipelineDefinition',
                ),
                'pipelineId' => array(
                    'required' => true,
                    'description' => 'Identifies the pipeline whose definition is to be validated.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'pipelineObjects' => array(
                    'required' => true,
                    'description' => 'A list of objects that define the pipeline changes to validate against the pipeline.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'PipelineObject',
                        'description' => 'Contains information about a pipeline object. This can be a logical, physical, or physical attempt pipeline object. The complete set of components of a pipeline defines the pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'required' => true,
                                'description' => 'Identifier of the object.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 1024,
                            ),
                            'name' => array(
                                'required' => true,
                                'description' => 'Name of the object.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 1024,
                            ),
                            'fields' => array(
                                'required' => true,
                                'description' => 'Key-value pairs that define the properties of the object.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Field',
                                    'description' => 'A key-value pair that describes a property of a pipeline object. The value is specified as either a string value (StringValue) or a reference to another object (RefValue) but not as both.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'key' => array(
                                            'required' => true,
                                            'description' => 'The field identifier.',
                                            'type' => 'string',
                                            'minLength' => 1,
                                            'maxLength' => 256,
                                        ),
                                        'stringValue' => array(
                                            'description' => 'The field value, expressed as a String.',
                                            'type' => 'string',
                                            'maxLength' => 10240,
                                        ),
                                        'refValue' => array(
                                            'description' => 'The field value, expressed as the identifier of another object.',
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
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An internal service error occurred.',
                    'class' => 'InternalServiceErrorException',
                ),
                array(
                    'reason' => 'The request was not valid. Verify that your request was properly formatted, that the signature was generated with the correct credentials, and that you haven\'t exceeded any of the service limits for your account.',
                    'class' => 'InvalidRequestException',
                ),
                array(
                    'reason' => 'The specified pipeline was not found. Verify that you used the correct user and account identifiers.',
                    'class' => 'PipelineNotFoundException',
                ),
                array(
                    'reason' => 'The specified pipeline has been deleted.',
                    'class' => 'PipelineDeletedException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'CreatePipelineOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'pipelineId' => array(
                    'description' => 'The ID that AWS Data Pipeline assigns the newly created pipeline. The ID is a string of the form: df-06372391ZG65EXAMPLE.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeObjectsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'pipelineObjects' => array(
                    'description' => 'An array of object definitions that are returned by the call to DescribeObjects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'PipelineObject',
                        'description' => 'Contains information about a pipeline object. This can be a logical, physical, or physical attempt pipeline object. The complete set of components of a pipeline defines the pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => 'Identifier of the object.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'Name of the object.',
                                'type' => 'string',
                            ),
                            'fields' => array(
                                'description' => 'Key-value pairs that define the properties of the object.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Field',
                                    'description' => 'A key-value pair that describes a property of a pipeline object. The value is specified as either a string value (StringValue) or a reference to another object (RefValue) but not as both.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'key' => array(
                                            'description' => 'The field identifier.',
                                            'type' => 'string',
                                        ),
                                        'stringValue' => array(
                                            'description' => 'The field value, expressed as a String.',
                                            'type' => 'string',
                                        ),
                                        'refValue' => array(
                                            'description' => 'The field value, expressed as the identifier of another object.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'marker' => array(
                    'description' => 'The starting point for the next page of results. To view the next page of results, call DescribeObjects again with this marker value.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'hasMoreResults' => array(
                    'description' => 'If True, there are more pages of results to return.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribePipelinesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'pipelineDescriptionList' => array(
                    'description' => 'An array of descriptions returned for the specified pipelines.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'PipelineDescription',
                        'description' => 'Contains pipeline metadata.',
                        'type' => 'object',
                        'properties' => array(
                            'pipelineId' => array(
                                'description' => 'The pipeline identifier that was assigned by AWS Data Pipeline. This is a string of the form df-297EG78HU43EEXAMPLE.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'Name of the pipeline.',
                                'type' => 'string',
                            ),
                            'fields' => array(
                                'description' => 'A list of read-only fields that contain metadata about the pipeline: @userId, @accountId, and @pipelineState.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Field',
                                    'description' => 'A key-value pair that describes a property of a pipeline object. The value is specified as either a string value (StringValue) or a reference to another object (RefValue) but not as both.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'key' => array(
                                            'description' => 'The field identifier.',
                                            'type' => 'string',
                                        ),
                                        'stringValue' => array(
                                            'description' => 'The field value, expressed as a String.',
                                            'type' => 'string',
                                        ),
                                        'refValue' => array(
                                            'description' => 'The field value, expressed as the identifier of another object.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'description' => array(
                                'description' => 'Description of the pipeline.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'EvaluateExpressionOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'evaluatedExpression' => array(
                    'description' => 'The evaluated expression.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'GetPipelineDefinitionOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'pipelineObjects' => array(
                    'description' => 'An array of objects defined in the pipeline.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'PipelineObject',
                        'description' => 'Contains information about a pipeline object. This can be a logical, physical, or physical attempt pipeline object. The complete set of components of a pipeline defines the pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => 'Identifier of the object.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'Name of the object.',
                                'type' => 'string',
                            ),
                            'fields' => array(
                                'description' => 'Key-value pairs that define the properties of the object.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Field',
                                    'description' => 'A key-value pair that describes a property of a pipeline object. The value is specified as either a string value (StringValue) or a reference to another object (RefValue) but not as both.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'key' => array(
                                            'description' => 'The field identifier.',
                                            'type' => 'string',
                                        ),
                                        'stringValue' => array(
                                            'description' => 'The field value, expressed as a String.',
                                            'type' => 'string',
                                        ),
                                        'refValue' => array(
                                            'description' => 'The field value, expressed as the identifier of another object.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ListPipelinesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'pipelineIdList' => array(
                    'description' => 'A list of all the pipeline identifiers that your account has permission to access. If you require additional information about the pipelines, you can use these identifiers to call DescribePipelines and GetPipelineDefinition.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'PipelineIdName',
                        'description' => 'Contains the name and identifier of a pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => 'Identifier of the pipeline that was assigned by AWS Data Pipeline. This is a string of the form df-297EG78HU43EEXAMPLE.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'Name of the pipeline.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'marker' => array(
                    'description' => 'If not null, indicates the starting point for the set of pipeline identifiers that the next call to ListPipelines will retrieve. If null, there are no more pipeline identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'hasMoreResults' => array(
                    'description' => 'If True, there are more results that can be obtained by a subsequent call to ListPipelines.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'PollForTaskOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'taskObject' => array(
                    'description' => 'An instance of PollForTaskResult, which contains an instance of TaskObject. The returned object contains all the information needed to complete the task that is being assigned to the task runner. One of the fields returned in this object is taskId, which contains an identifier for the task being assigned. The calling task runner uses taskId in subsequent calls to ReportTaskProgress and SetTaskStatus.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'taskId' => array(
                            'description' => 'An internal identifier for the task. This ID is passed to the SetTaskStatus and ReportTaskProgress actions.',
                            'type' => 'string',
                        ),
                        'pipelineId' => array(
                            'description' => 'Identifier of the pipeline that provided the task.',
                            'type' => 'string',
                        ),
                        'attemptId' => array(
                            'description' => 'Identifier of the pipeline task attempt object. AWS Data Pipeline uses this value to track how many times a task is attempted.',
                            'type' => 'string',
                        ),
                        'objects' => array(
                            'description' => 'Connection information for the location where the task runner will publish the output of the task.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'description' => 'Contains information about a pipeline object. This can be a logical, physical, or physical attempt pipeline object. The complete set of components of a pipeline defines the pipeline.',
                                'type' => 'object',
                                'properties' => array(
                                    'id' => array(
                                        'description' => 'Identifier of the object.',
                                        'type' => 'string',
                                    ),
                                    'name' => array(
                                        'description' => 'Name of the object.',
                                        'type' => 'string',
                                    ),
                                    'fields' => array(
                                        'description' => 'Key-value pairs that define the properties of the object.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Field',
                                            'description' => 'A key-value pair that describes a property of a pipeline object. The value is specified as either a string value (StringValue) or a reference to another object (RefValue) but not as both.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'key' => array(
                                                    'description' => 'The field identifier.',
                                                    'type' => 'string',
                                                ),
                                                'stringValue' => array(
                                                    'description' => 'The field value, expressed as a String.',
                                                    'type' => 'string',
                                                ),
                                                'refValue' => array(
                                                    'description' => 'The field value, expressed as the identifier of another object.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'PutPipelineDefinitionOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'validationErrors' => array(
                    'description' => 'A list of the validation errors that are associated with the objects defined in pipelineObjects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ValidationError',
                        'description' => 'Defines a validation error returned by PutPipelineDefinition or ValidatePipelineDefinition. Validation errors prevent pipeline activation. The set of validation errors that can be returned are defined by AWS Data Pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => 'The identifier of the object that contains the validation error.',
                                'type' => 'string',
                            ),
                            'errors' => array(
                                'description' => 'A description of the validation error.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'validationMessage',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'validationWarnings' => array(
                    'description' => 'A list of the validation warnings that are associated with the objects defined in pipelineObjects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ValidationWarning',
                        'description' => 'Defines a validation warning returned by PutPipelineDefinition or ValidatePipelineDefinition. Validation warnings do not prevent pipeline activation. The set of validation warnings that can be returned are defined by AWS Data Pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => 'The identifier of the object that contains the validation warning.',
                                'type' => 'string',
                            ),
                            'warnings' => array(
                                'description' => 'A description of the validation warning.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'validationMessage',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'errored' => array(
                    'description' => 'If True, there were validation errors. If errored is True, the pipeline definition is stored but cannot be activated until you correct the pipeline and call PutPipelineDefinition to commit the corrected pipeline.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'QueryObjectsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ids' => array(
                    'description' => 'A list of identifiers that match the query selectors.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'id',
                        'type' => 'string',
                    ),
                ),
                'marker' => array(
                    'description' => 'The starting point for the results to be returned. As long as the action returns HasMoreResults as True, you can call QueryObjects again and pass the marker value from the response to retrieve the next set of results.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'hasMoreResults' => array(
                    'description' => 'If True, there are more results that can be obtained by a subsequent call to QueryObjects.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'ReportTaskProgressOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'canceled' => array(
                    'description' => 'If True, the calling task runner should cancel processing of the task. The task runner does not need to call SetTaskStatus for canceled tasks.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'ReportTaskRunnerHeartbeatOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'terminate' => array(
                    'description' => 'Indicates whether the calling task runner should terminate. If True, the task runner that called ReportTaskRunnerHeartbeat should terminate.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
        'ValidatePipelineDefinitionOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'validationErrors' => array(
                    'description' => 'Lists the validation errors that were found by ValidatePipelineDefinition.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ValidationError',
                        'description' => 'Defines a validation error returned by PutPipelineDefinition or ValidatePipelineDefinition. Validation errors prevent pipeline activation. The set of validation errors that can be returned are defined by AWS Data Pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => 'The identifier of the object that contains the validation error.',
                                'type' => 'string',
                            ),
                            'errors' => array(
                                'description' => 'A description of the validation error.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'validationMessage',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'validationWarnings' => array(
                    'description' => 'Lists the validation warnings that were found by ValidatePipelineDefinition.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ValidationWarning',
                        'description' => 'Defines a validation warning returned by PutPipelineDefinition or ValidatePipelineDefinition. Validation warnings do not prevent pipeline activation. The set of validation warnings that can be returned are defined by AWS Data Pipeline.',
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => 'The identifier of the object that contains the validation warning.',
                                'type' => 'string',
                            ),
                            'warnings' => array(
                                'description' => 'A description of the validation warning.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'validationMessage',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'errored' => array(
                    'description' => 'If True, there were validation errors.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
            ),
        ),
    ),
);
