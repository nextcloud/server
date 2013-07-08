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
    'apiVersion' => '2012-06-01',
    'endpointPrefix' => 'glacier',
    'serviceFullName' => 'Amazon Glacier',
    'serviceType' => 'rest-json',
    'signatureVersion' => 'v4',
    'namespace' => 'Glacier',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'glacier.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'glacier.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'glacier.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'glacier.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'glacier.ap-northeast-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AbortMultipartUpload' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{accountId}/vaults/{vaultName}/multipart-uploads/{uploadId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This operation aborts a multipart upload identified by the upload ID.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'uploadId' => array(
                    'required' => true,
                    'description' => 'The upload ID of the multipart upload to delete.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'CompleteMultipartUpload' => array(
            'httpMethod' => 'POST',
            'uri' => '/{accountId}/vaults/{vaultName}/multipart-uploads/{uploadId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ArchiveCreationOutput',
            'responseType' => 'model',
            'summary' => 'You call this operation to inform Amazon Glacier that all the archive parts have been uploaded and that Amazon Glacier can now assemble the archive from the uploaded parts. After assembling and saving the archive to the vault, Amazon Glacier returns the URI path of the newly created archive resource. Using the URI path, you can then access the archive. After you upload an archive, you should save the archive ID returned to retrieve the archive at a later point. You can also get the vault inventory to obtain a list of archive IDs in a vault. For more information, see InitiateJob.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'uploadId' => array(
                    'required' => true,
                    'description' => 'The upload ID of the multipart upload.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'archiveSize' => array(
                    'description' => 'The total size, in bytes, of the entire archive. This value should be the sum of all the sizes of the individual parts that you uploaded.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-archive-size',
                ),
                'checksum' => array(
                    'description' => 'The SHA256 tree hash of the entire archive. It is the tree hash of SHA256 tree hash of the individual parts. If the value you specify in the request does not match the SHA256 tree hash of the final assembled archive as computed by Amazon Glacier, Amazon Glacier returns an error and the request fails.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-sha256-tree-hash',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'CreateVault' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{accountId}/vaults/{vaultName}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreateVaultOutput',
            'responseType' => 'model',
            'summary' => 'This operation creates a new vault with the specified name. The name of the vault must be unique within a region for an AWS account. You can create up to 1,000 vaults per account. If you need to create more vaults, contact Amazon Glacier.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
                array(
                    'reason' => 'Returned if the request results in a vault or account limit being exceeded.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'DeleteArchive' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{accountId}/vaults/{vaultName}/archives/{archiveId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This operation deletes an archive from a vault. Subsequent requests to initiate a retrieval of this archive will fail. Archive retrievals that are in progress for this archive ID may or may not succeed according to the following scenarios:',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'archiveId' => array(
                    'required' => true,
                    'description' => 'The ID of the archive to delete.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'DeleteVault' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{accountId}/vaults/{vaultName}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This operation deletes a vault. Amazon Glacier will delete a vault only if there are no archives in the vault as of the last inventory and there have been no writes to the vault since the last inventory. If either of these conditions is not satisfied, the vault deletion fails (that is, the vault is not removed) and Amazon Glacier returns an error. You can use DescribeVault to return the number of archives in a vault, and you can use Initiate a Job (POST jobs) to initiate a new inventory retrieval for a vault. The inventory contains the archive IDs you use to delete archives using Delete Archive (DELETE archive).',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'DeleteVaultNotifications' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{accountId}/vaults/{vaultName}/notification-configuration',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This operation deletes the notification configuration set for a vault. The operation is eventually consistent;that is, it might take some time for Amazon Glacier to completely disable the notifications and you might still receive some notifications for a short time after you send the delete request.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'DescribeJob' => array(
            'httpMethod' => 'GET',
            'uri' => '/{accountId}/vaults/{vaultName}/jobs/{jobId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GlacierJobDescription',
            'responseType' => 'model',
            'summary' => 'This operation returns information about a job you previously initiated, including the job initiation date, the user who initiated the job, the job status code/message and the Amazon SNS topic to notify after Amazon Glacier completes the job. For more information about initiating a job, see InitiateJob.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'jobId' => array(
                    'required' => true,
                    'description' => 'The ID of the job to describe.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'DescribeVault' => array(
            'httpMethod' => 'GET',
            'uri' => '/{accountId}/vaults/{vaultName}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'DescribeVaultOutput',
            'responseType' => 'model',
            'summary' => 'This operation returns information about a vault, including the vault\'s Amazon Resource Name (ARN), the date the vault was created, the number of archives it contains, and the total size of all the archives in the vault. The number of archives and their total size are as of the last inventory generation. This means that if you add or remove an archive from a vault, and then immediately use Describe Vault, the change in contents will not be immediately reflected. If you want to retrieve the latest inventory of the vault, use InitiateJob. Amazon Glacier generates vault inventories approximately daily. For more information, see Downloading a Vault Inventory in Amazon Glacier.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'GetJobOutput' => array(
            'httpMethod' => 'GET',
            'uri' => '/{accountId}/vaults/{vaultName}/jobs/{jobId}/output',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetJobOutputOutput',
            'responseType' => 'model',
            'summary' => 'This operation downloads the output of the job you initiated using InitiateJob. Depending on the job type you specified when you initiated the job, the output will be either the content of an archive or a vault inventory.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'jobId' => array(
                    'required' => true,
                    'description' => 'The job ID whose data is downloaded.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'range' => array(
                    'description' => 'The range of bytes to retrieve from the output. For example, if you want to download the first 1,048,576 bytes, specify "Range: bytes=0-1048575". By default, this operation downloads the entire output.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Range',
                ),
                'saveAs' => array(
                    'description' => 'Specify where the contents of the operation should be downloaded. Can be the path to a file, a resource returned by fopen, or a Guzzle\\Http\\EntityBodyInterface object.',
                    'location' => 'response_body',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'GetVaultNotifications' => array(
            'httpMethod' => 'GET',
            'uri' => '/{accountId}/vaults/{vaultName}/notification-configuration',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetVaultNotificationsOutput',
            'responseType' => 'model',
            'summary' => 'This operation retrieves the notification-configuration subresource of the specified vault.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'InitiateJob' => array(
            'httpMethod' => 'POST',
            'uri' => '/{accountId}/vaults/{vaultName}/jobs',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'InitiateJobOutput',
            'responseType' => 'model',
            'summary' => 'This operation initiates a job of the specified type. In this release, you can initiate a job to retrieve either an archive or a vault inventory (a list of archives in a vault).',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Format' => array(
                    'description' => 'When initiating a job to retrieve a vault inventory, you can optionally add this parameter to your request to specify the output format. If you are initiating an inventory job and do not specify a Format field, JSON is the default format. Valid Values are "CSV" and "JSON".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Type' => array(
                    'description' => 'The job type. You can initiate a job to retrieve an archive or get an inventory of a vault. Valid Values are "archive-retrieval" and "inventory-retrieval".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'ArchiveId' => array(
                    'description' => 'The ID of the archive that you want to retrieve. This field is required only if Type is set to archive-retrieval. An error occurs if you specify this request parameter for an inventory retrieval job request.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Description' => array(
                    'description' => 'The optional description for the job. The description must be less than or equal to 1,024 bytes. The allowable characters are 7-bit ASCII without control codes—specifically, ASCII values 32—126 decimal or 0x20—0x7E hexadecimal.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SNSTopic' => array(
                    'description' => 'The Amazon SNS topic ARN to which Amazon Glacier sends a notification when the job is completed and the output is ready for you to download. The specified topic publishes the notification to its subscribers. The SNS topic must exist.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'RetrievalByteRange' => array(
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'InitiateMultipartUpload' => array(
            'httpMethod' => 'POST',
            'uri' => '/{accountId}/vaults/{vaultName}/multipart-uploads',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'InitiateMultipartUploadOutput',
            'responseType' => 'model',
            'summary' => 'This operation initiates a multipart upload. Amazon Glacier creates a multipart upload resource and returns its ID in the response. The multipart upload ID is used in subsequent requests to upload parts of an archive (see UploadMultipartPart).',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'archiveDescription' => array(
                    'description' => 'The archive description that you are uploading in parts.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-archive-description',
                ),
                'partSize' => array(
                    'description' => 'The size of each part except the last, in bytes. The last part can be smaller than this part size.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-part-size',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'ListJobs' => array(
            'httpMethod' => 'GET',
            'uri' => '/{accountId}/vaults/{vaultName}/jobs',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListJobsOutput',
            'responseType' => 'model',
            'summary' => 'This operation lists jobs for a vault, including jobs that are in-progress and jobs that have recently finished.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'limit' => array(
                    'description' => 'Specifies that the response be limited to the specified number of items or fewer. If not specified, the List Jobs operation returns up to 1,000 jobs.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'marker' => array(
                    'description' => 'An opaque string used for pagination. This value specifies the job at which the listing of jobs should begin. Get the marker value from a previous List Jobs response. You need only include the marker if you are continuing the pagination of results started in a previous List Jobs request.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'statuscode' => array(
                    'description' => 'Specifies the type of job status to return. You can specify the following values: "InProgress", "Succeeded", or "Failed".',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'completed' => array(
                    'description' => 'Specifies the state of the jobs to return. You can specify true or false.',
                    'type' => 'string',
                    'location' => 'query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'ListMultipartUploads' => array(
            'httpMethod' => 'GET',
            'uri' => '/{accountId}/vaults/{vaultName}/multipart-uploads',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListMultipartUploadsOutput',
            'responseType' => 'model',
            'summary' => 'This operation lists in-progress multipart uploads for the specified vault. An in-progress multipart upload is a multipart upload that has been initiated by an InitiateMultipartUpload request, but has not yet been completed or aborted. The list returned in the List Multipart Upload response has no guaranteed order.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'limit' => array(
                    'description' => 'Specifies the maximum number of uploads returned in the response body. If this value is not specified, the List Uploads operation returns up to 1,000 uploads.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'marker' => array(
                    'description' => 'An opaque string used for pagination. This value specifies the upload at which the listing of uploads should begin. Get the marker value from a previous List Uploads response. You need only include the marker if you are continuing the pagination of results started in a previous List Uploads request.',
                    'type' => 'string',
                    'location' => 'query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'ListParts' => array(
            'httpMethod' => 'GET',
            'uri' => '/{accountId}/vaults/{vaultName}/multipart-uploads/{uploadId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListPartsOutput',
            'responseType' => 'model',
            'summary' => 'This operation lists the parts of an archive that have been uploaded in a specific multipart upload. You can make this request at any time during an in-progress multipart upload before you complete the upload (see CompleteMultipartUpload. List Parts returns an error for completed uploads. The list returned in the List Parts response is sorted by part range.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'uploadId' => array(
                    'required' => true,
                    'description' => 'The upload ID of the multipart upload.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'marker' => array(
                    'description' => 'An opaque string used for pagination. This value specifies the part at which the listing of parts should begin. Get the marker value from the response of a previous List Parts response. You need only include the marker if you are continuing the pagination of results started in a previous List Parts request.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'limit' => array(
                    'description' => 'Specifies the maximum number of parts returned in the response body. If this value is not specified, the List Parts operation returns up to 1,000 uploads.',
                    'type' => 'string',
                    'location' => 'query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'ListVaults' => array(
            'httpMethod' => 'GET',
            'uri' => '/{accountId}/vaults',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListVaultsOutput',
            'responseType' => 'model',
            'summary' => 'This operation lists all vaults owned by the calling user\'s account. The list returned in the response is ASCII-sorted by vault name.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'marker' => array(
                    'description' => 'A string used for pagination. The marker specifies the vault ARN after which the listing of vaults should begin.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'limit' => array(
                    'description' => 'The maximum number of items returned in the response. If you don\'t specify a value, the List Vaults operation returns up to 1,000 items.',
                    'type' => 'string',
                    'location' => 'query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'SetVaultNotifications' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{accountId}/vaults/{vaultName}/notification-configuration',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This operation configures notifications that will be sent when specific events happen to a vault. By default, you don\'t get any notifications.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SNSTopic' => array(
                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic Amazon Resource Name (ARN).',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Events' => array(
                    'description' => 'A list of one or more events for which Amazon Glacier will send a notification to the specified Amazon SNS topic.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'string',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'UploadArchive' => array(
            'httpMethod' => 'POST',
            'uri' => '/{accountId}/vaults/{vaultName}/archives',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ArchiveCreationOutput',
            'responseType' => 'model',
            'summary' => 'This operation adds an archive to a vault. This is a synchronous operation, and for a successful upload, your data is durably persisted. Amazon Glacier returns the archive ID in the x-amz-archive-id header of the response.',
            'parameters' => array(
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'archiveDescription' => array(
                    'description' => 'The optional description of the archive you are uploading.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-archive-description',
                ),
                'checksum' => array(
                    'description' => 'The SHA256 checksum (a linear hash) of the payload.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-sha256-tree-hash',
                ),
                'body' => array(
                    'description' => 'The data to upload.',
                    'type' => array(
                        'string',
                        'object',
                    ),
                    'location' => 'body',
                ),
                'ContentSHA256' => array(
                    'description' => 'SHA256 checksum of the body.',
                    'default' => true,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if, when uploading an archive, Amazon Glacier times out while receiving the upload.',
                    'class' => 'RequestTimeoutException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
        'UploadMultipartPart' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{accountId}/vaults/{vaultName}/multipart-uploads/{uploadId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'UploadMultipartPartOutput',
            'responseType' => 'model',
            'summary' => 'This operation uploads a part of an archive. You can upload archive parts in any order. You can also upload them in parallel. You can upload up to 10,000 parts for a multipart upload.',
            'parameters' => array(
                'accountId' => array(
                    'required' => true,
                    'description' => 'The AccountId is the AWS Account ID. You can specify either the AWS Account ID or optionally a \'-\', in which case Amazon Glacier uses the AWS Account ID associated with the credentials used to sign the request. If you specify your Account ID, do not include hyphens in it.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'vaultName' => array(
                    'required' => true,
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'uploadId' => array(
                    'required' => true,
                    'description' => 'The upload ID of the multipart upload.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'checksum' => array(
                    'description' => 'The SHA256 tree hash of the data being uploaded.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-sha256-tree-hash',
                ),
                'range' => array(
                    'description' => 'Identifies the range of bytes in the assembled archive that will be uploaded in this part. Amazon Glacier uses this information to assemble the archive in the proper sequence. The format of this header follows RFC 2616. An example header is Content-Range:bytes 0-4194303/*.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Range',
                ),
                'body' => array(
                    'description' => 'The data to upload.',
                    'type' => array(
                        'string',
                        'object',
                    ),
                    'location' => 'body',
                ),
                'ContentSHA256' => array(
                    'description' => 'SHA256 checksum of the body.',
                    'default' => true,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Returned if the specified resource, such as a vault, upload ID, or job ID, does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Returned if a parameter of the request is incorrectly specified.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'Returned if a required header or parameter is missing from the request.',
                    'class' => 'MissingParameterValueException',
                ),
                array(
                    'reason' => 'Returned if, when uploading an archive, Amazon Glacier times out while receiving the upload.',
                    'class' => 'RequestTimeoutException',
                ),
                array(
                    'reason' => 'Returned if the service cannot complete the request.',
                    'class' => 'ServiceUnavailableException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'ArchiveCreationOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'location' => array(
                    'description' => 'The relative URI path of the newly added archive resource.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Location',
                ),
                'checksum' => array(
                    'description' => 'The checksum of the archive computed by Amazon Glacier.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-sha256-tree-hash',
                ),
                'archiveId' => array(
                    'description' => 'The ID of the archive. This value is also included as part of the location.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-archive-id',
                ),
            ),
        ),
        'CreateVaultOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'location' => array(
                    'description' => 'The URI of the vault that was created.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Location',
                ),
            ),
        ),
        'GlacierJobDescription' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'JobId' => array(
                    'description' => 'An opaque string that identifies an Amazon Glacier job.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'JobDescription' => array(
                    'description' => 'The job description you provided when you initiated the job.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Action' => array(
                    'description' => 'The job type. It is either ArchiveRetrieval or InventoryRetrieval.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'ArchiveId' => array(
                    'description' => 'For an ArchiveRetrieval job, this is the archive ID requested for download. Otherwise, this field is null.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VaultARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the vault from which the archive retrieval was requested.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CreationDate' => array(
                    'description' => 'The UTC date when the job was created. A string representation of ISO 8601 date format, for example, "2012-03-20T17:03:43.221Z".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Completed' => array(
                    'description' => 'The job status. When a job is completed, you get the job\'s output.',
                    'type' => 'boolean',
                    'location' => 'json',
                ),
                'StatusCode' => array(
                    'description' => 'The status code can be InProgress, Succeeded, or Failed, and indicates the status of the job.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'StatusMessage' => array(
                    'description' => 'A friendly message that describes the job status.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'ArchiveSizeInBytes' => array(
                    'description' => 'For an ArchiveRetrieval job, this is the size in bytes of the archive being requested for download. For the InventoryRetrieval job, the value is null.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'InventorySizeInBytes' => array(
                    'description' => 'For an InventoryRetrieval job, this is the size in bytes of the inventory requested for download. For the ArchiveRetrieval job, the value is null.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'SNSTopic' => array(
                    'description' => 'An Amazon Simple Notification Service (Amazon SNS) topic that receives notification.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CompletionDate' => array(
                    'description' => 'The UTC time that the archive retrieval request completed. While the job is in progress, the value will be null.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SHA256TreeHash' => array(
                    'description' => 'For an ArchiveRetrieval job, it is the checksum of the archive. Otherwise, the value is null.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'ArchiveSHA256TreeHash' => array(
                    'type' => 'string',
                    'location' => 'json',
                ),
                'RetrievalByteRange' => array(
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeVaultOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VaultARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the vault.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VaultName' => array(
                    'description' => 'The name of the vault.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CreationDate' => array(
                    'description' => 'The UTC date when the vault was created. A string representation of ISO 8601 date format, for example, "2012-03-20T17:03:43.221Z".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'LastInventoryDate' => array(
                    'description' => 'The UTC date when Amazon Glacier completed the last vault inventory. A string representation of ISO 8601 date format, for example, "2012-03-20T17:03:43.221Z".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'NumberOfArchives' => array(
                    'description' => 'The number of archives in the vault as of the last inventory date. This field will return null if an inventory has not yet run on the vault, for example, if you just created the vault.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'SizeInBytes' => array(
                    'description' => 'Total size, in bytes, of the archives in the vault as of the last inventory date. This field will return null if an inventory has not yet run on the vault, for example, if you just created the vault.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'GetJobOutputOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'body' => array(
                    'description' => 'The job data, either archive data or inventory data.',
                    'type' => 'string',
                    'instanceOf' => 'Guzzle\\Http\\EntityBody',
                    'location' => 'body',
                ),
                'checksum' => array(
                    'description' => 'The checksum of the data in the response. This header is returned only when retrieving the output for an archive retrieval job. Furthermore, this header appears only under the following conditions: You get the entire range of the archive. You request a range to return of the archive that starts and ends on a multiple of 1 MB. For example, if you have an 3.1 MB archive and you specify a range to return that starts at 1 MB and ends at 2 MB, then the x-amz-sha256-tree-hash is returned as a response header. You request a range of the archive to return that starts on a multiple of 1 MB and goes to the end of the archive. For example, if you have a 3.1 MB archive and you specify a range that starts at 2 MB and ends at 3.1 MB (the end of the archive), then the x-amz-sha256-tree-hash is returned as a response header.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-sha256-tree-hash',
                ),
                'status' => array(
                    'description' => 'The HTTP response code for a job output request. The value depends on whether a range was specified in the request.',
                    'type' => 'numeric',
                    'location' => 'statusCode',
                ),
                'contentRange' => array(
                    'description' => 'The range of bytes returned by Amazon Glacier. If only partial output is downloaded, the response provides the range of bytes Amazon Glacier returned. For example, bytes 0-1048575/8388608 returns the first 1 MB from 8 MB.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Range',
                ),
                'acceptRanges' => array(
                    'description' => 'Indicates the range units accepted. For more information, go to RFC2616.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Accept-Ranges',
                ),
                'contentType' => array(
                    'description' => 'The Content-Type depends on whether the job output is an archive or a vault inventory. For archive data, the Content-Type is application/octet-stream. For vault inventory, if you requested CSV format when you initiated the job, the Content-Type is text/csv. Otherwise, by default, vault inventory is returned as JSON, and the Content-Type is application/json.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Type',
                ),
                'archiveDescription' => array(
                    'description' => 'The description of an archive.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-archive-description',
                ),
            ),
        ),
        'GetVaultNotificationsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SNSTopic' => array(
                    'description' => 'The Amazon Simple Notification Service (Amazon SNS) topic Amazon Resource Name (ARN).',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Events' => array(
                    'description' => 'A list of one or more events for which Amazon Glacier will send a notification to the specified Amazon SNS topic.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'string',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'InitiateJobOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'location' => array(
                    'description' => 'The relative URI path of the job.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Location',
                ),
                'jobId' => array(
                    'description' => 'The ID of the job.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-job-id',
                ),
            ),
        ),
        'InitiateMultipartUploadOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'location' => array(
                    'description' => 'The relative URI path of the multipart upload ID Amazon Glacier created.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Location',
                ),
                'uploadId' => array(
                    'description' => 'The ID of the multipart upload. This value is also included as part of the location.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-multipart-upload-id',
                ),
            ),
        ),
        'ListJobsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'JobList' => array(
                    'description' => 'A list of job objects. Each job object contains metadata describing the job.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'GlacierJobDescription',
                        'description' => 'Describes an Amazon Glacier job.',
                        'type' => 'object',
                        'properties' => array(
                            'JobId' => array(
                                'description' => 'An opaque string that identifies an Amazon Glacier job.',
                                'type' => 'string',
                            ),
                            'JobDescription' => array(
                                'description' => 'The job description you provided when you initiated the job.',
                                'type' => 'string',
                            ),
                            'Action' => array(
                                'description' => 'The job type. It is either ArchiveRetrieval or InventoryRetrieval.',
                                'type' => 'string',
                            ),
                            'ArchiveId' => array(
                                'description' => 'For an ArchiveRetrieval job, this is the archive ID requested for download. Otherwise, this field is null.',
                                'type' => 'string',
                            ),
                            'VaultARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the vault from which the archive retrieval was requested.',
                                'type' => 'string',
                            ),
                            'CreationDate' => array(
                                'description' => 'The UTC date when the job was created. A string representation of ISO 8601 date format, for example, "2012-03-20T17:03:43.221Z".',
                                'type' => 'string',
                            ),
                            'Completed' => array(
                                'description' => 'The job status. When a job is completed, you get the job\'s output.',
                                'type' => 'boolean',
                            ),
                            'StatusCode' => array(
                                'description' => 'The status code can be InProgress, Succeeded, or Failed, and indicates the status of the job.',
                                'type' => 'string',
                            ),
                            'StatusMessage' => array(
                                'description' => 'A friendly message that describes the job status.',
                                'type' => 'string',
                            ),
                            'ArchiveSizeInBytes' => array(
                                'description' => 'For an ArchiveRetrieval job, this is the size in bytes of the archive being requested for download. For the InventoryRetrieval job, the value is null.',
                                'type' => 'numeric',
                            ),
                            'InventorySizeInBytes' => array(
                                'description' => 'For an InventoryRetrieval job, this is the size in bytes of the inventory requested for download. For the ArchiveRetrieval job, the value is null.',
                                'type' => 'numeric',
                            ),
                            'SNSTopic' => array(
                                'description' => 'An Amazon Simple Notification Service (Amazon SNS) topic that receives notification.',
                                'type' => 'string',
                            ),
                            'CompletionDate' => array(
                                'description' => 'The UTC time that the archive retrieval request completed. While the job is in progress, the value will be null.',
                                'type' => 'string',
                            ),
                            'SHA256TreeHash' => array(
                                'description' => 'For an ArchiveRetrieval job, it is the checksum of the archive. Otherwise, the value is null.',
                                'type' => 'string',
                            ),
                            'ArchiveSHA256TreeHash' => array(
                                'type' => 'string',
                            ),
                            'RetrievalByteRange' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'An opaque string that represents where to continue pagination of the results. You use this value in a new List Jobs request to obtain more jobs in the list. If there are no more jobs, this value is null.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ListMultipartUploadsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'UploadsList' => array(
                    'description' => 'A list of in-progress multipart uploads.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'UploadListElement',
                        'description' => 'A list of in-progress multipart uploads for a vault.',
                        'type' => 'object',
                        'properties' => array(
                            'MultipartUploadId' => array(
                                'description' => 'The ID of a multipart upload.',
                                'type' => 'string',
                            ),
                            'VaultARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the vault that contains the archive.',
                                'type' => 'string',
                            ),
                            'ArchiveDescription' => array(
                                'description' => 'The description of the archive that was specified in the Initiate Multipart Upload request.',
                                'type' => 'string',
                            ),
                            'PartSizeInBytes' => array(
                                'description' => 'The part size, in bytes, specified in the Initiate Multipart Upload request. This is the size of all the parts in the upload except the last part, which may be smaller than this size.',
                                'type' => 'numeric',
                            ),
                            'CreationDate' => array(
                                'description' => 'The UTC time at which the multipart upload was initiated.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'An opaque string that represents where to continue pagination of the results. You use the marker in a new List Multipart Uploads request to obtain more uploads in the list. If there are no more uploads, this value is null.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ListPartsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'MultipartUploadId' => array(
                    'description' => 'The ID of the upload to which the parts are associated.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VaultARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the vault to which the multipart upload was initiated.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'ArchiveDescription' => array(
                    'description' => 'The description of the archive that was specified in the Initiate Multipart Upload request.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'PartSizeInBytes' => array(
                    'description' => 'The part size in bytes.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'CreationDate' => array(
                    'description' => 'The UTC time at which the multipart upload was initiated.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Parts' => array(
                    'description' => 'A list of the part sizes of the multipart upload.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'PartListElement',
                        'description' => 'A list of the part sizes of the multipart upload.',
                        'type' => 'object',
                        'properties' => array(
                            'RangeInBytes' => array(
                                'description' => 'The byte range of a part, inclusive of the upper value of the range.',
                                'type' => 'string',
                            ),
                            'SHA256TreeHash' => array(
                                'description' => 'The SHA256 tree hash value that Amazon Glacier calculated for the part. This field is never null.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'An opaque string that represents where to continue pagination of the results. You use the marker in a new List Parts request to obtain more jobs in the list. If there are no more parts, this value is null.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ListVaultsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VaultList' => array(
                    'description' => 'List of vaults.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'DescribeVaultOutput',
                        'description' => 'Contains the Amazon Glacier response to your request.',
                        'type' => 'object',
                        'properties' => array(
                            'VaultARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the vault.',
                                'type' => 'string',
                            ),
                            'VaultName' => array(
                                'description' => 'The name of the vault.',
                                'type' => 'string',
                            ),
                            'CreationDate' => array(
                                'description' => 'The UTC date when the vault was created. A string representation of ISO 8601 date format, for example, "2012-03-20T17:03:43.221Z".',
                                'type' => 'string',
                            ),
                            'LastInventoryDate' => array(
                                'description' => 'The UTC date when Amazon Glacier completed the last vault inventory. A string representation of ISO 8601 date format, for example, "2012-03-20T17:03:43.221Z".',
                                'type' => 'string',
                            ),
                            'NumberOfArchives' => array(
                                'description' => 'The number of archives in the vault as of the last inventory date. This field will return null if an inventory has not yet run on the vault, for example, if you just created the vault.',
                                'type' => 'numeric',
                            ),
                            'SizeInBytes' => array(
                                'description' => 'Total size, in bytes, of the archives in the vault as of the last inventory date. This field will return null if an inventory has not yet run on the vault, for example, if you just created the vault.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'The vault ARN at which to continue pagination of the results. You use the marker in another List Vaults request to obtain more vaults in the list.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'UploadMultipartPartOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'checksum' => array(
                    'description' => 'The SHA256 tree hash that Amazon Glacier computed for the uploaded part.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-sha256-tree-hash',
                ),
            ),
        ),
    ),
    'waiters' => array(
        '__default__' => array(
            'interval' => 3,
            'max_attempts' => 15,
        ),
        '__VaultState' => array(
            'operation' => 'DescribeVault',
        ),
        'VaultExists' => array(
            'extends' => '__VaultState',
            'success.type' => 'output',
            'description' => 'Wait until a vault can be accessed.',
            'ignore_errors' => array(
                'ResourceNotFoundException',
            ),
        ),
        'VaultNotExists' => array(
            'extends' => '__VaultState',
            'description' => 'Wait until a vault is deleted.',
            'success.type' => 'error',
            'success.value' => 'ResourceNotFoundException',
        ),
    ),
);
