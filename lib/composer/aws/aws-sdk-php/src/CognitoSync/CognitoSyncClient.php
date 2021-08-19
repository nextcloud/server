<?php
namespace Aws\CognitoSync;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Cognito Sync** service.
 *
 * @method \Aws\Result bulkPublish(array $args = [])
 * @method \GuzzleHttp\Promise\Promise bulkPublishAsync(array $args = [])
 * @method \Aws\Result deleteDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteDatasetAsync(array $args = [])
 * @method \Aws\Result describeDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDatasetAsync(array $args = [])
 * @method \Aws\Result describeIdentityPoolUsage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeIdentityPoolUsageAsync(array $args = [])
 * @method \Aws\Result describeIdentityUsage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeIdentityUsageAsync(array $args = [])
 * @method \Aws\Result getBulkPublishDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getBulkPublishDetailsAsync(array $args = [])
 * @method \Aws\Result getCognitoEvents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCognitoEventsAsync(array $args = [])
 * @method \Aws\Result getIdentityPoolConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getIdentityPoolConfigurationAsync(array $args = [])
 * @method \Aws\Result listDatasets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDatasetsAsync(array $args = [])
 * @method \Aws\Result listIdentityPoolUsage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listIdentityPoolUsageAsync(array $args = [])
 * @method \Aws\Result listRecords(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRecordsAsync(array $args = [])
 * @method \Aws\Result registerDevice(array $args = [])
 * @method \GuzzleHttp\Promise\Promise registerDeviceAsync(array $args = [])
 * @method \Aws\Result setCognitoEvents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise setCognitoEventsAsync(array $args = [])
 * @method \Aws\Result setIdentityPoolConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise setIdentityPoolConfigurationAsync(array $args = [])
 * @method \Aws\Result subscribeToDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise subscribeToDatasetAsync(array $args = [])
 * @method \Aws\Result unsubscribeFromDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise unsubscribeFromDatasetAsync(array $args = [])
 * @method \Aws\Result updateRecords(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRecordsAsync(array $args = [])
 */
class CognitoSyncClient extends AwsClient {}
