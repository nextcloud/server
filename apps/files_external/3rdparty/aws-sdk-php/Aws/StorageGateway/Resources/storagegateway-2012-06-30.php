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
    'apiVersion' => '2012-06-30',
    'endpointPrefix' => 'storagegateway',
    'serviceFullName' => 'AWS Storage Gateway',
    'serviceType' => 'json',
    'jsonVersion' => '1.1',
    'targetPrefix' => 'StorageGateway_20120630.',
    'signatureVersion' => 'v4',
    'namespace' => 'StorageGateway',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'storagegateway.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'storagegateway.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'storagegateway.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'storagegateway.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'storagegateway.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'storagegateway.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'storagegateway.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'storagegateway.sa-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'ActivateGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ActivateGatewayOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation activates the gateway you previously deployed on your VMware host. For more information, see Downloading and Deploying AWS Storage Gateway VM. In the activation process you specify information such as the region you want to use for storing snapshots, the time zone for scheduled snapshots and the gateway schedule window, an activation key, and a name for your gateway. The activation process also associates your gateway with your account (see UpdateGatewayInformation).',
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
                    'default' => 'StorageGateway_20120630.ActivateGateway',
                ),
                'ActivationKey' => array(
                    'required' => true,
                    'description' => 'Your gateway activation key. You can obtain the activation key by sending an HTTP GET request with redirects enabled to the gateway IP address (port 80). The redirect URL returned in the response provides you the activation key for your gateway in the query string parameter activationKey. It may also include other activation-related parameters, however, these are merely defaults -- the arguments you pass to the ActivateGateway API call determine the actual configuration of your gateway.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 50,
                ),
                'GatewayName' => array(
                    'required' => true,
                    'description' => 'A unique identifier for your gateway. This name becomes part of the gateway Amazon Resources Name (ARN) which is what you use as an input to other operations.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 2,
                    'maxLength' => 255,
                ),
                'GatewayTimezone' => array(
                    'required' => true,
                    'description' => 'One of the values that indicates the time zone you want to set for the gateway. The time zone is used, for example, for scheduling snapshots and your gateway\'s maintenance schedule.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'GMT-12:00',
                        'GMT-11:00',
                        'GMT-10:00',
                        'GMT-9:00',
                        'GMT-8:00',
                        'GMT-7:00',
                        'GMT-6:00',
                        'GMT-5:00',
                        'GMT-4:00',
                        'GMT-3:30',
                        'GMT-3:00',
                        'GMT-2:00',
                        'GMT-1:00',
                        'GMT',
                        'GMT+1:00',
                        'GMT+2:00',
                        'GMT+3:00',
                        'GMT+3:30',
                        'GMT+4:00',
                        'GMT+4:30',
                        'GMT+5:00',
                        'GMT+5:30',
                        'GMT+5:45',
                        'GMT+6:00',
                        'GMT+7:00',
                        'GMT+8:00',
                        'GMT+9:00',
                        'GMT+9:30',
                        'GMT+10:00',
                        'GMT+11:00',
                        'GMT+12:00',
                    ),
                ),
                'GatewayRegion' => array(
                    'required' => true,
                    'description' => 'One of the values that indicates the region where you want to store the snapshot backups. The gateway region specified must be the same region as the region in your Host header in the request. For more information about available regions and endpoints for AWS Storage Gateway, see Regions and Endpoints in the Amazon Web Services Glossary.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 25,
                ),
                'GatewayType' => array(
                    'description' => 'One of the values that defines the type of gateway to activate. The type specified is critical to all later functions of the gateway and cannot be changed after activation. The default value is STORED.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'STORED',
                        'CACHED',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'AddCache' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'AddCacheOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation configures one or more gateway local disks as cache for a cached-volume gateway. This operation is supported only for the gateway-cached volume architecture (see Storage Gateway Concepts).',
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
                    'default' => 'StorageGateway_20120630.AddCache',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'DiskIds' => array(
                    'required' => true,
                    'description' => 'An array of strings that identify disks that are to be configured as cache. Each string in the array must be minimum length of 1 and maximum length of 300. You can get the disk IDs from the ListLocalDisks API.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'DiskId',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 300,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'AddUploadBuffer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'AddUploadBufferOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation configures one or more gateway local disks as upload buffer for a specified gateway. This operation is supported for both the gateway-stored and gateway-cached volume architectures.',
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
                    'default' => 'StorageGateway_20120630.AddUploadBuffer',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'DiskIds' => array(
                    'required' => true,
                    'description' => 'An array of strings that identify disks that are to be configured as upload buffer. Each string in the array must be minimum length of 1 and maximum length of 300. You can get disk IDs from the ListLocalDisks API.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'DiskId',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 300,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'AddWorkingStorage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'AddWorkingStorageOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation configures one or more gateway local disks as working storage for a gateway. This operation is supported only for the gateway-stored volume architecture.',
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
                    'default' => 'StorageGateway_20120630.AddWorkingStorage',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'DiskIds' => array(
                    'required' => true,
                    'description' => 'An array of strings that identify disks that are to be configured as working storage. Each string have a minimum length of 1 and maximum length of 300. You can get the disk IDs from the ListLocalDisks API.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'DiskId',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 300,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'CreateCachediSCSIVolume' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateCachediSCSIVolumeOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation creates a cached volume on a specified cached gateway. This operation is supported only for the gateway-cached volume architecture.',
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
                    'default' => 'StorageGateway_20120630.CreateCachediSCSIVolume',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'VolumeSizeInBytes' => array(
                    'required' => true,
                    'description' => 'The size of the cached volume.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'SnapshotId' => array(
                    'description' => 'The snapshot ID (e.g., "snap-1122aabb") of the snapshot to restore as the new stored volume. Specify this field if you want to create the iSCSI cached volume from a snapshot; otherwise, do not include this field. To list snapshots for your account, use DescribeSnapshots in Amazon Elastic Compute Cloud API Reference.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'TargetName' => array(
                    'required' => true,
                    'description' => 'The name of the iSCSI target used by initiators to connect to the target and as a suffix for the target ARN. For example, specifying TargetName as myvolume results in the target ARN of arn:aws:storagegateway:us-east-1:111122223333:gateway/mygateway/target/iqn.1997-05.com.amazon:myvolume. The target name must be unique across all volumes of a gateway.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 200,
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'description' => 'The network interface of the gateway on which to expose the iSCSI target. Only IPv4 addresses are accepted. Use the DescribeGatewayInformation operation to get a list of the network interfaces available on the gateway.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'ClientToken' => array(
                    'required' => true,
                    'description' => 'A unique identifying string for the cached volume.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 5,
                    'maxLength' => 100,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'CreateSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateSnapshotOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation initiates a snapshot of a volume.',
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
                    'default' => 'StorageGateway_20120630.CreateSnapshot',
                ),
                'VolumeARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the volume. Use the ListVolumes operation to return a list of gateway volumes.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'SnapshotDescription' => array(
                    'required' => true,
                    'description' => 'Textual description of the snapshot that appears in the Amazon EC2 console, Elastic Block Store snapshots panel in the Description field, and in the AWS Storage Gateway snapshot Details pane, Description field',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'CreateSnapshotFromVolumeRecoveryPoint' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateSnapshotFromVolumeRecoveryPointOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation initiates a snapshot of a gateway from a volume recovery point. This operation is supported only for the gateway-cached volume architecture (see StorageGatewayConcepts).',
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
                    'default' => 'StorageGateway_20120630.CreateSnapshotFromVolumeRecoveryPoint',
                ),
                'VolumeARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the volume. Use the ListVolumes operation to return a list of gateway volumes.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'SnapshotDescription' => array(
                    'required' => true,
                    'description' => 'A textual description of the snapshot that appears in the Amazon EC2 console, Elastic Block Store snapshots panel in the Description field, and in the AWS Storage Gateway snapshot Details pane, Description field.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'CreateStorediSCSIVolume' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateStorediSCSIVolumeOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation creates a volume on a specified gateway. This operation is supported only for the gateway-cached volume architecture.',
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
                    'default' => 'StorageGateway_20120630.CreateStorediSCSIVolume',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'DiskId' => array(
                    'required' => true,
                    'description' => 'The unique identifier for the gateway local disk that is configured as a stored volume. Use ListLocalDisks to list disk IDs for a gateway.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 300,
                ),
                'SnapshotId' => array(
                    'description' => 'The snapshot ID (e.g. "snap-1122aabb") of the snapshot to restore as the new stored volume. Specify this field if you want to create the iSCSI storage volume from a snapshot otherwise do not include this field. To list snapshots for your account use DescribeSnapshots in the Amazon Elastic Compute Cloud API Reference.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'PreserveExistingData' => array(
                    'required' => true,
                    'description' => 'Specify this field as true if you want to preserve the data on the local disk. Otherwise, specifying this field as false creates an empty volume.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'TargetName' => array(
                    'required' => true,
                    'description' => 'The name of the iSCSI target used by initiators to connect to the target and as a suffix for the target ARN. For example, specifying TargetName as myvolume results in the target ARN of arn:aws:storagegateway:us-east-1:111122223333:gateway/mygateway/target/iqn.1997-05.com.amazon:myvolume. The target name must be unique across all volumes of a gateway.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 200,
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'description' => 'The network interface of the gateway on which to expose the iSCSI target. Only IPv4 addresses are accepted. Use DescribeGatewayInformation to get a list of the network interfaces available on a gateway.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DeleteBandwidthRateLimit' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DeleteBandwidthRateLimitOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation deletes the bandwidth rate limits of a gateway. You can delete either the upload and download bandwidth rate limit, or you can delete both. If you delete only one of the limits, the other limit remains unchanged. To specify which gateway to work with, use the Amazon Resource Name (ARN) of the gateway in your request.',
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
                    'default' => 'StorageGateway_20120630.DeleteBandwidthRateLimit',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'BandwidthType' => array(
                    'required' => true,
                    'description' => 'One of the BandwidthType values that indicates the gateway bandwidth rate limit to delete.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'UPLOAD',
                        'DOWNLOAD',
                        'ALL',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DeleteChapCredentials' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DeleteChapCredentialsOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation deletes Challenge-Handshake Authentication Protocol (CHAP) credentials for a specified iSCSI target and initiator pair.',
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
                    'default' => 'StorageGateway_20120630.DeleteChapCredentials',
                ),
                'TargetARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the iSCSI volume target. Use the DescribeStorediSCSIVolumes operation to return to retrieve the TargetARN for specified VolumeARN.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 800,
                ),
                'InitiatorName' => array(
                    'required' => true,
                    'description' => 'The iSCSI initiator that connects to the target.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DeleteGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DeleteGatewayOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation deletes a gateway. To specify which gateway to delete, use the Amazon Resource Name (ARN) of the gateway in your request. The operation deletes the gateway; however, it does not delete the gateway virtual machine (VM) from your host computer.',
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
                    'default' => 'StorageGateway_20120630.DeleteGateway',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DeleteSnapshotSchedule' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DeleteSnapshotScheduleOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation deletes a snapshot of a volume.',
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
                    'default' => 'StorageGateway_20120630.DeleteSnapshotSchedule',
                ),
                'VolumeARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the volume. Use the ListVolumes operation to return a list of gateway volumes.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DeleteVolume' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DeleteVolumeOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation delete the specified gateway volume that you previously created using the CreateStorediSCSIVolume API. For gateway-stored volumes, the local disk that was configured as the storage volume is not deleted. You can reuse the local disk to create another storage volume.',
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
                    'default' => 'StorageGateway_20120630.DeleteVolume',
                ),
                'VolumeARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the volume. Use the ListVolumes operation to return a list of gateway volumes.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeBandwidthRateLimit' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeBandwidthRateLimitOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns the bandwidth rate limits of a gateway. By default, these limits are not set, which means no bandwidth rate limiting is in effect.',
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
                    'default' => 'StorageGateway_20120630.DescribeBandwidthRateLimit',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeCache' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeCacheOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns information about the cache of a gateway. This operation is supported only for the gateway-cached volume architecture.',
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
                    'default' => 'StorageGateway_20120630.DescribeCache',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeCachediSCSIVolumes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeCachediSCSIVolumesOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns a description of the gateway volumes specified in the request. This operation is supported only for the gateway-cached volume architecture.',
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
                    'default' => 'StorageGateway_20120630.DescribeCachediSCSIVolumes',
                ),
                'VolumeARNs' => array(
                    'required' => true,
                    'description' => 'An array of strings, where each string represents the Amazon Resource Name (ARN) of a cached volume. All of the specified cached volumes must be from the same gateway. Use ListVolumes to get volume ARNs of a gateway.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'VolumeARN',
                        'type' => 'string',
                        'minLength' => 50,
                        'maxLength' => 500,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeChapCredentials' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeChapCredentialsOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns an array of Challenge-Handshake Authentication Protocol (CHAP) credentials information for a specified iSCSI target, one for each target-initiator pair.',
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
                    'default' => 'StorageGateway_20120630.DescribeChapCredentials',
                ),
                'TargetARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the iSCSI volume target. Use the DescribeStorediSCSIVolumes operation to return to retrieve the TargetARN for specified VolumeARN.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 800,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeGatewayInformation' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeGatewayInformationOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns metadata about a gateway such as its name, network interfaces, configured time zone, and the state (whether the gateway is running or not). To specify which gateway to describe, use the Amazon Resource Name (ARN) of the gateway in your request.',
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
                    'default' => 'StorageGateway_20120630.DescribeGatewayInformation',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeMaintenanceStartTime' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeMaintenanceStartTimeOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns your gateway\'s weekly maintenance start time including the day and time of the week. Note that values are in terms of the gateway\'s time zone.',
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
                    'default' => 'StorageGateway_20120630.DescribeMaintenanceStartTime',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeSnapshotSchedule' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeSnapshotScheduleOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation describes the snapshot schedule for the specified gateway volume. The snapshot schedule information includes intervals at which snapshots are automatically initiated on the volume.',
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
                    'default' => 'StorageGateway_20120630.DescribeSnapshotSchedule',
                ),
                'VolumeARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the volume. Use the ListVolumes operation to return a list of gateway volumes.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeStorediSCSIVolumes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeStorediSCSIVolumesOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns description of the gateway volumes specified in the request. The list of gateway volumes in the request must be from one gateway. In the response Amazon Storage Gateway returns volume information sorted by volume ARNs.',
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
                    'default' => 'StorageGateway_20120630.DescribeStorediSCSIVolumes',
                ),
                'VolumeARNs' => array(
                    'required' => true,
                    'description' => 'An array of strings where each string represents the Amazon Resource Name (ARN) of a stored volume. All of the specified stored volumes must from the same gateway. Use ListVolumes to get volume ARNs for a gateway.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'VolumeARN',
                        'type' => 'string',
                        'minLength' => 50,
                        'maxLength' => 500,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeUploadBuffer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeUploadBufferOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns information about the upload buffer of a gateway. This operation is supported for both the gateway-stored and gateway-cached volume architectures.',
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
                    'default' => 'StorageGateway_20120630.DescribeUploadBuffer',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeWorkingStorage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeWorkingStorageOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns information about the working storage of a gateway. This operation is supported only for the gateway-stored volume architecture.',
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
                    'default' => 'StorageGateway_20120630.DescribeWorkingStorage',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'ListGateways' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ListGatewaysOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation lists gateways owned by an AWS account in a region specified in the request. The returned list is ordered by gateway Amazon Resource Name (ARN).',
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
                    'default' => 'StorageGateway_20120630.ListGateways',
                ),
                'Marker' => array(
                    'description' => 'An opaque string that indicates the position at which to begin the returned list of gateways.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1000,
                ),
                'Limit' => array(
                    'description' => 'Specifies that the list of gateways returned be limited to the specified number of items.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'ListLocalDisks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ListLocalDisksOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation returns a list of the local disks of a gateway. To specify which gateway to describe you use the Amazon Resource Name (ARN) of the gateway in the body of the request.',
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
                    'default' => 'StorageGateway_20120630.ListLocalDisks',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'ListVolumeRecoveryPoints' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ListVolumeRecoveryPointsOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation lists the recovery points for a specified gateway. This operation is supported only for the gateway-cached volume architecture.',
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
                    'default' => 'StorageGateway_20120630.ListVolumeRecoveryPoints',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'ListVolumes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ListVolumesOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation lists the iSCSI stored volumes of a gateway. Results are sorted by volume ARN. The response includes only the volume ARNs. If you want additional volume information, use the DescribeStorediSCSIVolumes API.',
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
                    'default' => 'StorageGateway_20120630.ListVolumes',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'Marker' => array(
                    'description' => 'A string that indicates the position at which to begin the returned list of volumes. Obtain the marker from the response of a previous List iSCSI Volumes request.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 1000,
                ),
                'Limit' => array(
                    'description' => 'Specifies that the list of volumes returned be limited to the specified number of items.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'ShutdownGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ShutdownGatewayOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation shuts down a gateway. To specify which gateway to shut down, use the Amazon Resource Name (ARN) of the gateway in the body of your request.',
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
                    'default' => 'StorageGateway_20120630.ShutdownGateway',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'StartGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'StartGatewayOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation starts a gateway that you previously shut down (see ShutdownGateway). After the gateway starts, you can then make other API calls, your applications can read from or write to the gateway\'s storage volumes and you will be able to take snapshot backups.',
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
                    'default' => 'StorageGateway_20120630.StartGateway',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'UpdateBandwidthRateLimit' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'UpdateBandwidthRateLimitOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation updates the bandwidth rate limits of a gateway. You can update both the upload and download bandwidth rate limit or specify only one of the two. If you don\'t set a bandwidth rate limit, the existing rate limit remains.',
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
                    'default' => 'StorageGateway_20120630.UpdateBandwidthRateLimit',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'AverageUploadRateLimitInBitsPerSec' => array(
                    'description' => 'The average upload bandwidth rate limit in bits per second.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 51200,
                ),
                'AverageDownloadRateLimitInBitsPerSec' => array(
                    'description' => 'The average download bandwidth rate limit in bits per second.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 102400,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'UpdateChapCredentials' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'UpdateChapCredentialsOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation updates the Challenge-Handshake Authentication Protocol (CHAP) credentials for a specified iSCSI target. By default, a gateway does not have CHAP enabled; however, for added security, you might use it.',
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
                    'default' => 'StorageGateway_20120630.UpdateChapCredentials',
                ),
                'TargetARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the iSCSI volume target. Use the DescribeStorediSCSIVolumes operation to return to retrieve the TargetARN for specified VolumeARN.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 800,
                ),
                'SecretToAuthenticateInitiator' => array(
                    'required' => true,
                    'description' => 'The secret key that the initiator (e.g. Windows client) must provide to participate in mutual CHAP with the target.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 12,
                    'maxLength' => 16,
                ),
                'InitiatorName' => array(
                    'required' => true,
                    'description' => 'The iSCSI initiator that connects to the target.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'SecretToAuthenticateTarget' => array(
                    'description' => 'The secret key that the target must provide to participate in mutual CHAP with the initiator (e.g. Windows client).',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 12,
                    'maxLength' => 16,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'UpdateGatewayInformation' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'UpdateGatewayInformationOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation updates a gateway\'s metadata, which includes the gateway\'s name and time zone. To specify which gateway to update, use the Amazon Resource Name (ARN) of the gateway in your request.',
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
                    'default' => 'StorageGateway_20120630.UpdateGatewayInformation',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'GatewayName' => array(
                    'description' => 'A unique identifier for your gateway. This name becomes part of the gateway Amazon Resources Name (ARN) which is what you use as an input to other operations.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 2,
                    'maxLength' => 255,
                ),
                'GatewayTimezone' => array(
                    'description' => 'One of the GatewayTimezone values that represents the time zone for your gateway. The time zone is used, for example, when a time stamp is given to a snapshot.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'GMT-12:00',
                        'GMT-11:00',
                        'GMT-10:00',
                        'GMT-9:00',
                        'GMT-8:00',
                        'GMT-7:00',
                        'GMT-6:00',
                        'GMT-5:00',
                        'GMT-4:00',
                        'GMT-3:30',
                        'GMT-3:00',
                        'GMT-2:00',
                        'GMT-1:00',
                        'GMT',
                        'GMT+1:00',
                        'GMT+2:00',
                        'GMT+3:00',
                        'GMT+3:30',
                        'GMT+4:00',
                        'GMT+4:30',
                        'GMT+5:00',
                        'GMT+5:30',
                        'GMT+5:45',
                        'GMT+6:00',
                        'GMT+7:00',
                        'GMT+8:00',
                        'GMT+9:00',
                        'GMT+9:30',
                        'GMT+10:00',
                        'GMT+11:00',
                        'GMT+12:00',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'UpdateGatewaySoftwareNow' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'UpdateGatewaySoftwareNowOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation updates the gateway virtual machine (VM) software. The request immediately triggers the software update.',
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
                    'default' => 'StorageGateway_20120630.UpdateGatewaySoftwareNow',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'UpdateMaintenanceStartTime' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'UpdateMaintenanceStartTimeOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation updates a gateway\'s weekly maintenance start time information, including day and time of the week. The maintenance time is the time in your gateway\'s time zone.',
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
                    'default' => 'StorageGateway_20120630.UpdateMaintenanceStartTime',
                ),
                'GatewayARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'HourOfDay' => array(
                    'required' => true,
                    'description' => 'The hour component of the maintenance start time represented as hh, where hh is the hour (00 to 23). The hour of the day is in the time zone of the gateway.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 23,
                ),
                'MinuteOfHour' => array(
                    'required' => true,
                    'description' => 'The minute component of the maintenance start time represented as mm, where mm is the minute (00 to 59). The minute of the hour is in the time zone of the gateway.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 59,
                ),
                'DayOfWeek' => array(
                    'required' => true,
                    'description' => 'The maintenance start time day of the week.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 6,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'UpdateSnapshotSchedule' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'UpdateSnapshotScheduleOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'This operation updates a snapshot schedule configured for a gateway volume.',
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
                    'default' => 'StorageGateway_20120630.UpdateSnapshotSchedule',
                ),
                'VolumeARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the volume. Use the ListVolumes operation to return a list of gateway volumes.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 50,
                    'maxLength' => 500,
                ),
                'StartAt' => array(
                    'required' => true,
                    'description' => 'The hour of the day at which the snapshot schedule begins represented as hh, where hh is the hour (0 to 23). The hour of the day is in the time zone of the gateway.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'maximum' => 23,
                ),
                'RecurrenceInHours' => array(
                    'required' => true,
                    'description' => 'Frequency of snapshots. Specify the number of hours between snapshots.',
                    'type' => 'numeric',
                    'location' => 'json',
                    'minimum' => 1,
                    'maximum' => 24,
                ),
                'Description' => array(
                    'description' => 'Optional description of the snapshot that overwrites the existing description.',
                    'type' => 'string',
                    'location' => 'json',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'An exception occured because an invalid gateway request was issued to the service. See the error and message fields for more information.',
                    'class' => 'InvalidGatewayRequestException',
                ),
                array(
                    'reason' => 'An internal server error has occured during the request. See the error and message fields for more information.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
    ),
    'models' => array(
        'ActivateGatewayOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'AddCacheOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'AddUploadBufferOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'AddWorkingStorageOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateCachediSCSIVolumeOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeARN' => array(
                    'description' => 'The ARN of the configured volume.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'TargetARN' => array(
                    'description' => 'The ARN of the volume target that includes the iSCSI name that initiators can use to connect to the target.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateSnapshotOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the volume of which the snapshot was taken.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SnapshotId' => array(
                    'description' => 'The snapshot ID that is used to refer to the snapshot in future operations such as describing snapshots (Amazon Elastic Compute Cloud API DescribeSnapshots) or creating a volume from a snapshot (CreateStorediSCSIVolume).',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateSnapshotFromVolumeRecoveryPointOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SnapshotId' => array(
                    'description' => 'The snapshot ID that is used to refer to the snapshot in future operations such as describing snapshots (Amazon Elastic Compute Cloud API DescribeSnapshots) or creating a volume from a snapshot (CreateStorediSCSIVolume).',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VolumeARN' => array(
                    'description' => 'The ARN of the volume of which the snapshot was taken. Obtain volume ARNs from the ListVolumes operation.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VolumeRecoveryPointTime' => array(
                    'description' => 'The time of the recovery point. Data up to this recovery point are included in the snapshot.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateStorediSCSIVolumeOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the configured volume.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VolumeSizeInBytes' => array(
                    'description' => 'The size of the volume in bytes.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'TargetARN' => array(
                    'description' => 'he Amazon Resource Name (ARN) of the volume target that includes the iSCSI name that initiators can use to connect to the target.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DeleteBandwidthRateLimitOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DeleteChapCredentialsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TargetARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the target.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'InitiatorName' => array(
                    'description' => 'The iSCSI initiator that connects to the target.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DeleteGatewayOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DeleteSnapshotScheduleOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the volume of which the snapshot was taken.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DeleteVolumeOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the storage volume that was deleted. It is the same ARN you provided in the request.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeBandwidthRateLimitOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AverageUploadRateLimitInBitsPerSec' => array(
                    'description' => 'The average upload bandwidth rate limit in bits per second. This field does not appear in the response if the upload rate limit is not set.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'AverageDownloadRateLimitInBitsPerSec' => array(
                    'description' => 'The average download bandwidth rate limit in bits per second. This field does not appear in the response if the download rate limit is not set.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeCacheOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'In response, AWS Storage Gateway returns the ARN of the activated gateway. If you don\'t remember the ARN of a gateway, you can use the List Gateways operations to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DiskIds' => array(
                    'description' => 'An array of the gateway\'s local disk IDs that are configured as cache. Each local disk ID is specified as a string (minimum length of 1 and maximum length of 300). If no local disks are configured as cache, then the DiskIds array is empty.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'DiskId',
                        'type' => 'string',
                    ),
                ),
                'CacheAllocatedInBytes' => array(
                    'description' => 'The size allocated, in bytes, for the cache. If no cache is defined for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'CacheUsedPercentage' => array(
                    'description' => 'The percentage (0 to 100) of the cache storage in use. If no cached is defined for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'CacheDirtyPercentage' => array(
                    'description' => 'The percentage of the cache that contains data that has not yet been persisted to Amazon S3. If no cached is defined for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'CacheHitPercentage' => array(
                    'description' => 'The percentage (0 to 100) of data read from the storage volume that was read from cache. If no cached is defined for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'CacheMissPercentage' => array(
                    'description' => 'TThe percentage (0 to 100) of data read from the storage volume that was not read from the cache, but was read from Amazon S3. If no cached is defined for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeCachediSCSIVolumesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CachediSCSIVolumes' => array(
                    'description' => 'An array of CachediSCSIVolume objects where each object contains metadata about one cached volume.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'CachediSCSIVolume',
                        'description' => 'Describes a cached storage volume.',
                        'type' => 'object',
                        'properties' => array(
                            'VolumeARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the storage volume.',
                                'type' => 'string',
                            ),
                            'VolumeId' => array(
                                'description' => 'The unique identifier of the storage volume, e.g. vol-1122AABB.',
                                'type' => 'string',
                            ),
                            'VolumeType' => array(
                                'description' => 'A value describing the type of volume.',
                                'type' => 'string',
                            ),
                            'VolumeStatus' => array(
                                'description' => 'A value that indicates the state of the volume.',
                                'type' => 'string',
                            ),
                            'VolumeSizeInBytes' => array(
                                'description' => 'The size of the volume in bytes that was specified in the API_CreateCachediSCSIVolume operation.',
                                'type' => 'numeric',
                            ),
                            'VolumeProgress' => array(
                                'description' => 'The percentage complete if the volume is restoring or bootstrapping that represents the percent of data transferred. This field does not appear in the response if the stored volume is not restoring or bootstrapping.',
                                'type' => 'numeric',
                            ),
                            'SourceSnapshotId' => array(
                                'description' => 'If the cached volume was created from a snapshot, this field contains the snapshot ID used, e.g. snap-1122aabb. Otherwise, this field is not included.',
                                'type' => 'string',
                            ),
                            'VolumeiSCSIAttributes' => array(
                                'description' => 'Lists iSCSI information about a volume.',
                                'type' => 'object',
                                'properties' => array(
                                    'TargetARN' => array(
                                        'description' => 'The Amazon Resource Name (ARN) of the volume target.',
                                        'type' => 'string',
                                    ),
                                    'NetworkInterfaceId' => array(
                                        'description' => 'The network interface identifier.',
                                        'type' => 'string',
                                    ),
                                    'NetworkInterfacePort' => array(
                                        'description' => 'The port used to communicate with iSCSI targets.',
                                        'type' => 'numeric',
                                    ),
                                    'LunNumber' => array(
                                        'description' => 'The logical disk number.',
                                        'type' => 'numeric',
                                    ),
                                    'ChapEnabled' => array(
                                        'description' => 'Indicates whether mutual CHAP is enabled for the iSCSI target.',
                                        'type' => 'boolean',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeChapCredentialsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ChapCredentials' => array(
                    'description' => 'An array of ChapInfo objects that represent CHAP credentials. Each object in the array contains CHAP credential information for one target-initiator pair. If no CHAP credentials are set, an empty array is returned. CHAP credential information is provided in a JSON object with the following fields:',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ChapInfo',
                        'description' => 'Describes Challenge-Handshake Authentication Protocol (CHAP) information that supports authentication between your gateway and iSCSI initiators.',
                        'type' => 'object',
                        'properties' => array(
                            'TargetARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the volume.',
                                'type' => 'string',
                            ),
                            'SecretToAuthenticateInitiator' => array(
                                'description' => 'The secret key that the initiator (e.g. Windows client) must provide to participate in mutual CHAP with the target.',
                                'type' => 'string',
                            ),
                            'InitiatorName' => array(
                                'description' => 'The iSCSI initiator that connects to the target.',
                                'type' => 'string',
                            ),
                            'SecretToAuthenticateTarget' => array(
                                'description' => 'The secret key that the target must provide to participate in mutual CHAP with the initiator (e.g. Windows client).',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeGatewayInformationOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'GatewayId' => array(
                    'description' => 'The gateway ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'GatewayTimezone' => array(
                    'description' => 'One of the GatewayTimezone values that indicates the time zone configured for the gateway.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'GatewayState' => array(
                    'description' => 'One of the GatewayState values that indicates the operating state of the gateway.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'GatewayNetworkInterfaces' => array(
                    'description' => 'A NetworkInterface array that contains descriptions of the gateway network interfaces.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'NetworkInterface',
                        'description' => 'Describes a gateway\'s network interface.',
                        'type' => 'object',
                        'properties' => array(
                            'Ipv4Address' => array(
                                'description' => 'The Internet Protocol version 4 (IPv4) address of the interface.',
                                'type' => 'string',
                            ),
                            'MacAddress' => array(
                                'description' => 'The Media Access Control (MAC) address of the interface.',
                                'type' => 'string',
                            ),
                            'Ipv6Address' => array(
                                'description' => 'The Internet Protocol version 6 (IPv6) address of the interface. Currently not supported.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'GatewayType' => array(
                    'description' => 'TBD',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'NextUpdateAvailabilityDate' => array(
                    'description' => 'The date at which an update to the gateway is available. This date is in the time zone of the gateway. If the gateway is not available for an update this field is not returned in the response.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeMaintenanceStartTimeOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'HourOfDay' => array(
                    'description' => 'The hour component of the maintenance start time represented as hh, where hh is the hour (0 to 23). The hour of the day is in the time zone of the gateway.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'MinuteOfHour' => array(
                    'description' => 'The minute component of the maintenance start time represented as mm, where mm is the minute (0 to 59). The minute of the hour is in the time zone of the gateway.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'DayOfWeek' => array(
                    'description' => 'An ordinal number between 0 and 6 that represents the day of the week, where 0 represents Sunday and 6 represents Saturday. The day of week is in the time zone of the gateway.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'Timezone' => array(
                    'description' => 'One of the GatewayTimezone values that indicates the time zone that is set for the gateway. The start time and day of week specified should be in the time zone of the gateway.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeSnapshotScheduleOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the volume that was specified in the request.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'StartAt' => array(
                    'description' => 'The hour of the day at which the snapshot schedule begins represented as hh, where hh is the hour (0 to 23). The hour of the day is in the time zone of the gateway.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'RecurrenceInHours' => array(
                    'description' => 'The number of hours between snapshots.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'Description' => array(
                    'description' => 'The snapshot description.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Timezone' => array(
                    'description' => 'One of the GatewayTimezone values that indicates the time zone of the gateway.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeStorediSCSIVolumesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StorediSCSIVolumes' => array(
                    'description' => 'Describes a single unit of output from DescribeStorediSCSIVolumes. The following fields are returned:',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'StorediSCSIVolume',
                        'description' => 'Describes an iSCSI stored volume.',
                        'type' => 'object',
                        'properties' => array(
                            'VolumeARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the storage volume.',
                                'type' => 'string',
                            ),
                            'VolumeId' => array(
                                'description' => 'The unique identifier of the volume, e.g. vol-AE4B946D.',
                                'type' => 'string',
                            ),
                            'VolumeType' => array(
                                'description' => 'One of the VolumeType enumeration values describing the type of the volume.',
                                'type' => 'string',
                            ),
                            'VolumeStatus' => array(
                                'description' => 'One of the VolumeStatus values that indicates the state of the storage volume.',
                                'type' => 'string',
                            ),
                            'VolumeSizeInBytes' => array(
                                'description' => 'The size of the volume in bytes.',
                                'type' => 'numeric',
                            ),
                            'VolumeProgress' => array(
                                'description' => 'Represents the percentage complete if the volume is restoring or bootstrapping that represents the percent of data transferred. This field does not appear in the response if the stored volume is not restoring or bootstrapping.',
                                'type' => 'numeric',
                            ),
                            'VolumeDiskId' => array(
                                'description' => 'The disk ID of the local disk that was specified in the CreateStorediSCSIVolume operation.',
                                'type' => 'string',
                            ),
                            'SourceSnapshotId' => array(
                                'description' => 'If the stored volume was created from a snapshot, this field contains the snapshot ID used, e.g. snap-78e22663. Otherwise, this field is not included.',
                                'type' => 'string',
                            ),
                            'PreservedExistingData' => array(
                                'description' => 'Indicates if when the stored volume was created, existing data on the underlying local disk was preserved.',
                                'type' => 'boolean',
                            ),
                            'VolumeiSCSIAttributes' => array(
                                'description' => 'An VolumeiSCSIAttributes object that represents a collection of iSCSI attributes for one stored volume.',
                                'type' => 'object',
                                'properties' => array(
                                    'TargetARN' => array(
                                        'description' => 'The Amazon Resource Name (ARN) of the volume target.',
                                        'type' => 'string',
                                    ),
                                    'NetworkInterfaceId' => array(
                                        'description' => 'The network interface identifier.',
                                        'type' => 'string',
                                    ),
                                    'NetworkInterfacePort' => array(
                                        'description' => 'The port used to communicate with iSCSI targets.',
                                        'type' => 'numeric',
                                    ),
                                    'LunNumber' => array(
                                        'description' => 'The logical disk number.',
                                        'type' => 'numeric',
                                    ),
                                    'ChapEnabled' => array(
                                        'description' => 'Indicates whether mutual CHAP is enabled for the iSCSI target.',
                                        'type' => 'boolean',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeUploadBufferOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'In response, AWS Storage Gateway returns the ARN of the activated gateway. If you don\'t remember the ARN of a gateway, you can use the ListGateways operations to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DiskIds' => array(
                    'description' => 'An array of the gateway\'s local disk IDs that are configured as working storage. Each local disk ID is specified as a string (minimum length of 1 and maximum length of 300). If no local disks are configured as working storage, then the DiskIds array is empty.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'DiskId',
                        'type' => 'string',
                    ),
                ),
                'UploadBufferUsedInBytes' => array(
                    'description' => 'The total upload buffer in bytes in use by the gateway. If no upload buffer is configured for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'UploadBufferAllocatedInBytes' => array(
                    'description' => 'The total upload buffer in bytes allocated for the gateway. If no upload buffer is configured for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeWorkingStorageOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DiskIds' => array(
                    'description' => 'An array of the gateway\'s local disk IDs that are configured as working storage. Each local disk ID is specified as a string (minimum length of 1 and maximum length of 300). If no local disks are configured as working storage, then the DiskIds array is empty.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'DiskId',
                        'type' => 'string',
                    ),
                ),
                'WorkingStorageUsedInBytes' => array(
                    'description' => 'The total working storage in bytes in use by the gateway. If no working storage is configured for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'WorkingStorageAllocatedInBytes' => array(
                    'description' => 'The total working storage in bytes allocated for the gateway. If no working storage is configured for the gateway, this field returns 0.',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
            ),
        ),
        'ListGatewaysOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Gateways' => array(
                    'description' => 'An array of GatewayInfo objects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'GatewayInfo',
                        'description' => 'Describes a gateway; contains one data member, the GatewayARN of this gateway.',
                        'type' => 'object',
                        'properties' => array(
                            'GatewayARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'Use the marker in your next request to fetch the next set of gateways in the list. If there are no more gateways to list, this field does not appear in the response.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ListLocalDisksOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Disks' => array(
                    'description' => 'An array of Disk objects.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Disk',
                        'description' => 'Describes a gateway local disk.',
                        'type' => 'object',
                        'properties' => array(
                            'DiskId' => array(
                                'description' => 'The unique device ID or other distinguishing data that identify the local disk.',
                                'type' => 'string',
                            ),
                            'DiskPath' => array(
                                'description' => 'The path of the local disk in the gateway virtual machine (VM).',
                                'type' => 'string',
                            ),
                            'DiskNode' => array(
                                'description' => 'The device node of the local disk as assigned by the virtualization environment.',
                                'type' => 'string',
                            ),
                            'DiskSizeInBytes' => array(
                                'description' => 'The local disk size in bytes.',
                                'type' => 'numeric',
                            ),
                            'DiskAllocationType' => array(
                                'description' => 'One of the DiskAllocationType enumeration values that identifies how the local disk is used.',
                                'type' => 'string',
                            ),
                            'DiskAllocationResource' => array(
                                'description' => 'The iSCSI Qualified Name (IQN) that is defined for the disk. This field is not included in the response if the local disk is not defined as an iSCSI target. The format of this field is targetIqn::LUNNumber::region-volumeId.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ListVolumeRecoveryPointsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the activated gateway whose local disk information is returned.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VolumeRecoveryPointInfos' => array(
                    'description' => 'An array of VolumeRecoveryPointInfo objects, where each object describes a recovery point. If no recovery points are defined for the volume, then VolumeRecoveryPointInfos is an empty array "[]"',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'VolumeRecoveryPointInfo',
                        'description' => 'Lists information about the recovery points of a cached volume.',
                        'type' => 'object',
                        'properties' => array(
                            'VolumeARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the volume associated with the recovery point.',
                                'type' => 'string',
                            ),
                            'VolumeSizeInBytes' => array(
                                'description' => 'The size, in bytes, of the volume to which the recovery point is associated.',
                                'type' => 'numeric',
                            ),
                            'VolumeUsageInBytes' => array(
                                'description' => 'The size, in bytes, of the volume in use at the time of the recovery point.',
                                'type' => 'numeric',
                            ),
                            'VolumeRecoveryPointTime' => array(
                                'description' => 'The time of the recovery point. The format of the time is in the ISO8601 extended YYYY-MM-DD\'T\'HH:MM:SS\'Z\' format.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ListVolumesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Marker' => array(
                    'description' => 'Use the marker in your next request to continue pagination of iSCSI volumes. If there are no more volumes to list, this field does not appear in the response body.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VolumeInfos' => array(
                    'description' => 'An array of VolumeInfo objects, where each object describes an iSCSI volume. If no volumes are defined for the gateway, then VolumeInfos is an empty array "[]".',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'VolumeInfo',
                        'description' => 'Describes a storage volume.',
                        'type' => 'object',
                        'properties' => array(
                            'VolumeARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) for the storage volume. For example, the following is a valid ARN:',
                                'type' => 'string',
                            ),
                            'VolumeType' => array(
                                'description' => 'One of the VolumeType values that indicates the configuration of the storage volume, for example as a storage volume.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ShutdownGatewayOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'StartGatewayOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'UpdateBandwidthRateLimitOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'UpdateChapCredentialsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TargetARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the target. This is the same target specified in the request.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'InitiatorName' => array(
                    'description' => 'The iSCSI initiator that connects to the target. This is the same initiator name specified in the request.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'UpdateGatewayInformationOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'UpdateGatewaySoftwareNowOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'UpdateMaintenanceStartTimeOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GatewayARN' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the gateway. Use the ListGateways operation to return a list of gateways for your account and region.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'UpdateSnapshotScheduleOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeARN' => array(
                    'description' => 'The UpdateSnapshotScheduleOutput$VolumeARN of the storage volume whose snapshot schedule was updated. It is the same value you provided in your request.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeCachediSCSIVolumes' => array(
                'result_key' => 'CachediSCSIVolumes',
            ),
            'DescribeStorediSCSIVolumes' => array(
                'result_key' => 'StorediSCSIVolumes',
            ),
            'ListGateways' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'Limit',
                'result_key' => 'Gateways',
            ),
            'ListLocalDisks' => array(
                'result_key' => 'Disks',
            ),
            'ListVolumeRecoveryPoints' => array(
                'result_key' => 'VolumeRecoveryPointInfos',
            ),
            'ListVolumes' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'Limit',
                'result_key' => 'VolumeInfos',
            ),
        ),
    ),
);
