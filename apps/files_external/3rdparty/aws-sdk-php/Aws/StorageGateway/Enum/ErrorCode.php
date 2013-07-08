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

namespace Aws\StorageGateway\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable ErrorCode values
 */
class ErrorCode extends Enum
{
    const ACTIVATION_KEY_EXPIRED = 'ActivationKeyExpired';
    const ACTIVATION_KEY_INVALID = 'ActivationKeyInvalid';
    const ACTIVATION_KEY_NOT_FOUND = 'ActivationKeyNotFound';
    const GATEWAY_INTERNAL_ERROR = 'GatewayInternalError';
    const GATEWAY_NOT_CONNECTED = 'GatewayNotConnected';
    const GATEWAY_NOT_FOUND = 'GatewayNotFound';
    const GATEWAY_PROXY_NETWORK_CONNECTION_BUSY = 'GatewayProxyNetworkConnectionBusy';
    const AUTHENTICATION_FAILURE = 'AuthenticationFailure';
    const BANDWIDTH_THROTTLE_SCHEDULE_NOT_FOUND = 'BandwidthThrottleScheduleNotFound';
    const BLOCKED = 'Blocked';
    const CANNOT_EXPORT_SNAPSHOT = 'CannotExportSnapshot';
    const CHAP_CREDENTIAL_NOT_FOUND = 'ChapCredentialNotFound';
    const DISK_ALREADY_ALLOCATED = 'DiskAlreadyAllocated';
    const DISK_DOES_NOT_EXIST = 'DiskDoesNotExist';
    const DISK_SIZE_GREATER_THAN_VOLUME_MAX_SIZE = 'DiskSizeGreaterThanVolumeMaxSize';
    const DISK_SIZE_LESS_THAN_VOLUME_SIZE = 'DiskSizeLessThanVolumeSize';
    const DISK_SIZE_NOT_GIG_ALIGNED = 'DiskSizeNotGigAligned';
    const DUPLICATE_CERTIFICATE_INFO = 'DuplicateCertificateInfo';
    const DUPLICATE_SCHEDULE = 'DuplicateSchedule';
    const ENDPOINT_NOT_FOUND = 'EndpointNotFound';
    const IAM_NOT_SUPPORTED = 'IAMNotSupported';
    const INITIATOR_INVALID = 'InitiatorInvalid';
    const INITIATOR_NOT_FOUND = 'InitiatorNotFound';
    const INTERNAL_ERROR = 'InternalError';
    const INVALID_GATEWAY = 'InvalidGateway';
    const INVALID_ENDPOINT = 'InvalidEndpoint';
    const INVALID_PARAMETERS = 'InvalidParameters';
    const INVALID_SCHEDULE = 'InvalidSchedule';
    const LOCAL_STORAGE_LIMIT_EXCEEDED = 'LocalStorageLimitExceeded';
    const LUN_ALREADY_ALLOCATED  = 'LunAlreadyAllocated ';
    const LUN_INVALID = 'LunInvalid';
    const MAXIMUM_CONTENT_LENGTH_EXCEEDED = 'MaximumContentLengthExceeded';
    const MAXIMUM_VOLUME_COUNT_EXCEEDED = 'MaximumVolumeCountExceeded';
    const NETWORK_CONFIGURATION_CHANGED = 'NetworkConfigurationChanged';
    const NO_DISKS_AVAILABLE = 'NoDisksAvailable';
    const NOT_IMPLEMENTED = 'NotImplemented';
    const NOT_SUPPORTED = 'NotSupported';
    const OPERATION_ABORTED = 'OperationAborted';
    const OUTDATED_GATEWAY = 'OutdatedGateway';
    const PARAMETERS_NOT_IMPLEMENTED = 'ParametersNotImplemented';
    const REGION_INVALID = 'RegionInvalid';
    const REQUEST_TIMEOUT = 'RequestTimeout';
    const SERVICE_UNAVAILABLE = 'ServiceUnavailable';
    const SNAPSHOT_DELETED = 'SnapshotDeleted';
    const SNAPSHOT_ID_INVALID = 'SnapshotIdInvalid';
    const SNAPSHOT_IN_PROGRESS = 'SnapshotInProgress';
    const SNAPSHOT_NOT_FOUND = 'SnapshotNotFound';
    const SNAPSHOT_SCHEDULE_NOT_FOUND = 'SnapshotScheduleNotFound';
    const STAGING_AREA_FULL = 'StagingAreaFull';
    const STORAGE_FAILURE = 'StorageFailure';
    const TARGET_ALREADY_EXISTS = 'TargetAlreadyExists';
    const TARGET_INVALID = 'TargetInvalid';
    const TARGET_NOT_FOUND = 'TargetNotFound';
    const UNAUTHORIZED_OPERATION = 'UnauthorizedOperation';
    const VOLUME_ALREADY_EXISTS = 'VolumeAlreadyExists';
    const VOLUME_ID_INVALID = 'VolumeIdInvalid';
    const VOLUME_IN_USE = 'VolumeInUse';
    const VOLUME_NOT_FOUND = 'VolumeNotFound';
    const VOLUME_NOT_READY = 'VolumeNotReady';
}
