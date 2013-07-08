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
    'apiVersion' => '2010-06-01',
    'endpointPrefix' => 'importexport',
    'serviceFullName' => 'AWS Import/Export',
    'serviceType' => 'query',
    'globalEndpoint' => 'importexport.amazonaws.com',
    'resultWrapped' => true,
    'signatureVersion' => 'v2',
    'namespace' => 'ImportExport',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'importexport.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'importexport.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'importexport.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'importexport.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'importexport.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'importexport.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'importexport.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'importexport.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CancelJob' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CancelJobOutput',
            'responseType' => 'model',
            'summary' => 'This operation cancels a specified job. Only the job owner can cancel it. The operation fails if the job has already started or is complete.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CancelJob',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-06-01',
                ),
                'JobId' => array(
                    'required' => true,
                    'description' => 'A unique identifier which refers to a particular job.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The JOBID was missing, not found, or not associated with the AWS account.',
                    'class' => 'InvalidJobIdException',
                ),
                array(
                    'reason' => 'Indicates that the specified job has expired out of the system.',
                    'class' => 'ExpiredJobIdException',
                ),
                array(
                    'reason' => 'The specified job ID has been canceled and is no longer valid.',
                    'class' => 'CanceledJobIdException',
                ),
                array(
                    'reason' => 'AWS Import/Export cannot cancel the job',
                    'class' => 'UnableToCancelJobIdException',
                ),
                array(
                    'reason' => 'The AWS Access Key ID specified in the request did not match the manifest\'s accessKeyId value. The manifest and the request authentication must use the same AWS Access Key ID.',
                    'class' => 'InvalidAccessKeyIdException',
                ),
            ),
        ),
        'CreateJob' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateJobOutput',
            'responseType' => 'model',
            'summary' => 'This operation initiates the process of scheduling an upload or download of your data. You include in the request a manifest that describes the data transfer specifics. The response to the request includes a job ID, which you can use in other operations, a signature that you use to identify your storage device, and the address where you should ship your storage device.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateJob',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-06-01',
                ),
                'JobType' => array(
                    'required' => true,
                    'description' => 'Specifies whether the job to initiate is an import or export job.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Import',
                        'Export',
                    ),
                ),
                'Manifest' => array(
                    'required' => true,
                    'description' => 'The UTF-8 encoded text of the manifest file.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ManifestAddendum' => array(
                    'description' => 'For internal use only.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ValidateOnly' => array(
                    'required' => true,
                    'description' => 'Validate the manifest and parameter values in the request but do not actually create a job.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameters was missing from the request.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'One or more parameters had an invalid value.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'One or more parameters had an invalid value.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'The AWS Access Key ID specified in the request did not match the manifest\'s accessKeyId value. The manifest and the request authentication must use the same AWS Access Key ID.',
                    'class' => 'InvalidAccessKeyIdException',
                ),
                array(
                    'reason' => 'The address specified in the manifest is invalid.',
                    'class' => 'InvalidAddressException',
                ),
                array(
                    'reason' => 'One or more manifest fields was invalid. Please correct and resubmit.',
                    'class' => 'InvalidManifestFieldException',
                ),
                array(
                    'reason' => 'One or more required fields were missing from the manifest file. Please correct and resubmit.',
                    'class' => 'MissingManifestFieldException',
                ),
                array(
                    'reason' => 'The specified bucket does not exist. Create the specified bucket or change the manifest\'s bucket, exportBucket, or logBucket field to a bucket that the account, as specified by the manifest\'s Access Key ID, has write permissions to.',
                    'class' => 'NoSuchBucketException',
                ),
                array(
                    'reason' => 'One or more required customs parameters was missing from the manifest.',
                    'class' => 'MissingCustomsException',
                ),
                array(
                    'reason' => 'One or more customs parameters was invalid. Please correct and resubmit.',
                    'class' => 'InvalidCustomsException',
                ),
                array(
                    'reason' => 'File system specified in export manifest is invalid.',
                    'class' => 'InvalidFileSystemException',
                ),
                array(
                    'reason' => 'Your manifest file contained buckets from multiple regions. A job is restricted to buckets from one region. Please correct and resubmit.',
                    'class' => 'MultipleRegionsException',
                ),
                array(
                    'reason' => 'The account specified does not have the appropriate bucket permissions.',
                    'class' => 'BucketPermissionException',
                ),
                array(
                    'reason' => 'Your manifest is not well-formed.',
                    'class' => 'MalformedManifestException',
                ),
            ),
        ),
        'GetStatus' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetStatusOutput',
            'responseType' => 'model',
            'summary' => 'This operation returns information about a job, including where the job is in the processing pipeline, the status of the results, and the signature value associated with the job. You can only return information about jobs you own.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetStatus',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-06-01',
                ),
                'JobId' => array(
                    'required' => true,
                    'description' => 'A unique identifier which refers to a particular job.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The JOBID was missing, not found, or not associated with the AWS account.',
                    'class' => 'InvalidJobIdException',
                ),
                array(
                    'reason' => 'Indicates that the specified job has expired out of the system.',
                    'class' => 'ExpiredJobIdException',
                ),
                array(
                    'reason' => 'The specified job ID has been canceled and is no longer valid.',
                    'class' => 'CanceledJobIdException',
                ),
                array(
                    'reason' => 'The AWS Access Key ID specified in the request did not match the manifest\'s accessKeyId value. The manifest and the request authentication must use the same AWS Access Key ID.',
                    'class' => 'InvalidAccessKeyIdException',
                ),
            ),
        ),
        'ListJobs' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListJobsOutput',
            'responseType' => 'model',
            'summary' => 'This operation returns the jobs associated with the requester. AWS Import/Export lists the jobs in reverse chronological order based on the date of creation. For example if Job Test1 was created 2009Dec30 and Test2 was created 2010Feb05, the ListJobs operation would return Test2 followed by Test1.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListJobs',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-06-01',
                ),
                'MaxJobs' => array(
                    'description' => 'Sets the maximum number of jobs returned in the response. If there are additional jobs that were not returned because MaxJobs was exceeded, the response contains &lt;IsTruncated&gt;true&lt;/IsTruncated&gt;. To return the additional jobs, see Marker.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'Specifies the JOBID to start after when listing the jobs created with your account. AWS Import/Export lists your jobs in reverse chronological order. See MaxJobs.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more parameters had an invalid value.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'The AWS Access Key ID specified in the request did not match the manifest\'s accessKeyId value. The manifest and the request authentication must use the same AWS Access Key ID.',
                    'class' => 'InvalidAccessKeyIdException',
                ),
            ),
        ),
        'UpdateJob' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UpdateJobOutput',
            'responseType' => 'model',
            'summary' => 'You use this operation to change the parameters specified in the original manifest file by supplying a new manifest file. The manifest file attached to this request replaces the original manifest file. You can only use the operation after a CreateJob request but before the data transfer starts and you can only use it on jobs you own.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateJob',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-06-01',
                ),
                'JobId' => array(
                    'required' => true,
                    'description' => 'A unique identifier which refers to a particular job.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Manifest' => array(
                    'required' => true,
                    'description' => 'The UTF-8 encoded text of the manifest file.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'JobType' => array(
                    'required' => true,
                    'description' => 'Specifies whether the job to initiate is an import or export job.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Import',
                        'Export',
                    ),
                ),
                'ValidateOnly' => array(
                    'required' => true,
                    'description' => 'Validate the manifest and parameter values in the request but do not actually create a job.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more required parameters was missing from the request.',
                    'class' => 'MissingParameterException',
                ),
                array(
                    'reason' => 'One or more parameters had an invalid value.',
                    'class' => 'InvalidParameterException',
                ),
                array(
                    'reason' => 'The AWS Access Key ID specified in the request did not match the manifest\'s accessKeyId value. The manifest and the request authentication must use the same AWS Access Key ID.',
                    'class' => 'InvalidAccessKeyIdException',
                ),
                array(
                    'reason' => 'The address specified in the manifest is invalid.',
                    'class' => 'InvalidAddressException',
                ),
                array(
                    'reason' => 'One or more manifest fields was invalid. Please correct and resubmit.',
                    'class' => 'InvalidManifestFieldException',
                ),
                array(
                    'reason' => 'The JOBID was missing, not found, or not associated with the AWS account.',
                    'class' => 'InvalidJobIdException',
                ),
                array(
                    'reason' => 'One or more required fields were missing from the manifest file. Please correct and resubmit.',
                    'class' => 'MissingManifestFieldException',
                ),
                array(
                    'reason' => 'The specified bucket does not exist. Create the specified bucket or change the manifest\'s bucket, exportBucket, or logBucket field to a bucket that the account, as specified by the manifest\'s Access Key ID, has write permissions to.',
                    'class' => 'NoSuchBucketException',
                ),
                array(
                    'reason' => 'Indicates that the specified job has expired out of the system.',
                    'class' => 'ExpiredJobIdException',
                ),
                array(
                    'reason' => 'The specified job ID has been canceled and is no longer valid.',
                    'class' => 'CanceledJobIdException',
                ),
                array(
                    'reason' => 'One or more required customs parameters was missing from the manifest.',
                    'class' => 'MissingCustomsException',
                ),
                array(
                    'reason' => 'One or more customs parameters was invalid. Please correct and resubmit.',
                    'class' => 'InvalidCustomsException',
                ),
                array(
                    'reason' => 'File system specified in export manifest is invalid.',
                    'class' => 'InvalidFileSystemException',
                ),
                array(
                    'reason' => 'Your manifest file contained buckets from multiple regions. A job is restricted to buckets from one region. Please correct and resubmit.',
                    'class' => 'MultipleRegionsException',
                ),
                array(
                    'reason' => 'The account specified does not have the appropriate bucket permissions.',
                    'class' => 'BucketPermissionException',
                ),
                array(
                    'reason' => 'Your manifest is not well-formed.',
                    'class' => 'MalformedManifestException',
                ),
            ),
        ),
    ),
    'models' => array(
        'CancelJobOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Success' => array(
                    'description' => 'Specifies whether (true) or not (false) AWS Import/Export updated your job.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
            ),
        ),
        'CreateJobOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'JobId' => array(
                    'description' => 'A unique identifier which refers to a particular job.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'JobType' => array(
                    'description' => 'Specifies whether the job to initiate is an import or export job.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'AwsShippingAddress' => array(
                    'description' => 'Address you ship your storage device to.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Signature' => array(
                    'description' => 'An encrypted code used to authenticate the request and response, for example, "DV+TpDfx1/TdSE9ktyK9k/bDTVI=". Only use this value is you want to create the signature file yourself. Generally you should use the SignatureFileContents value.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'SignatureFileContents' => array(
                    'description' => 'The actual text of the SIGNATURE file to be written to disk.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'WarningMessage' => array(
                    'description' => 'An optional message notifying you of non-fatal issues with the job, such as use of an incompatible Amazon S3 bucket name.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'GetStatusOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'JobId' => array(
                    'description' => 'A unique identifier which refers to a particular job.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'JobType' => array(
                    'description' => 'Specifies whether the job to initiate is an import or export job.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'AwsShippingAddress' => array(
                    'description' => 'Address you ship your storage device to.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LocationCode' => array(
                    'description' => 'A token representing the location of the storage device, such as "AtAWS".',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LocationMessage' => array(
                    'description' => 'A more human readable form of the physical location of the storage device.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ProgressCode' => array(
                    'description' => 'A token representing the state of the job, such as "Started".',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ProgressMessage' => array(
                    'description' => 'A more human readable form of the job status.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Carrier' => array(
                    'description' => 'Name of the shipping company. This value is included when the LocationCode is "Returned".',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'TrackingNumber' => array(
                    'description' => 'The shipping tracking number assigned by AWS Import/Export to the storage device when it\'s returned to you. We return this value when the LocationCode is "Returned".',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LogBucket' => array(
                    'description' => 'Amazon S3 bucket for user logs.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LogKey' => array(
                    'description' => 'The key where the user logs were stored.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ErrorCount' => array(
                    'description' => 'Number of errors. We return this value when the ProgressCode is Success or SuccessWithErrors.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'Signature' => array(
                    'description' => 'An encrypted code used to authenticate the request and response, for example, "DV+TpDfx1/TdSE9ktyK9k/bDTVI=". Only use this value is you want to create the signature file yourself. Generally you should use the SignatureFileContents value.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'SignatureFileContents' => array(
                    'description' => 'An encrypted code used to authenticate the request and response, for example, "DV+TpDfx1/TdSE9ktyK9k/bDTVI=". Only use this value is you want to create the signature file yourself. Generally you should use the SignatureFileContents value.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CurrentManifest' => array(
                    'description' => 'The last manifest submitted, which will be used to process the job.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CreationDate' => array(
                    'description' => 'Timestamp of the CreateJob request in ISO8601 date format. For example "2010-03-28T20:27:35Z".',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListJobsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Jobs' => array(
                    'description' => 'A list container for Jobs returned by the ListJobs operation.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Job',
                        'description' => 'Representation of a job returned by the ListJobs operation.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'JobId' => array(
                                'description' => 'A unique identifier which refers to a particular job.',
                                'type' => 'string',
                            ),
                            'CreationDate' => array(
                                'description' => 'Timestamp of the CreateJob request in ISO8601 date format. For example "2010-03-28T20:27:35Z".',
                                'type' => 'string',
                            ),
                            'IsCanceled' => array(
                                'description' => 'Indicates whether the job was canceled.',
                                'type' => 'boolean',
                            ),
                            'JobType' => array(
                                'description' => 'Specifies whether the job to initiate is an import or export job.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'Indicates whether the list of jobs was truncated. If true, then call ListJobs again using the last JobId element as the marker.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
            ),
        ),
        'UpdateJobOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Success' => array(
                    'description' => 'Specifies whether (true) or not (false) AWS Import/Export updated your job.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'WarningMessage' => array(
                    'description' => 'An optional message notifying you of non-fatal issues with the job, such as use of an incompatible Amazon S3 bucket name.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'ListJobs' => array(
                'token_param' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxJobs',
                'result_key' => 'Jobs',
            ),
        ),
    ),
);
