<?php
namespace Aws\CloudHSMV2;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS CloudHSM V2** service.
 * @method \Aws\Result copyBackupToRegion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise copyBackupToRegionAsync(array $args = [])
 * @method \Aws\Result createCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createClusterAsync(array $args = [])
 * @method \Aws\Result createHsm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHsmAsync(array $args = [])
 * @method \Aws\Result deleteBackup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteBackupAsync(array $args = [])
 * @method \Aws\Result deleteCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteClusterAsync(array $args = [])
 * @method \Aws\Result deleteHsm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHsmAsync(array $args = [])
 * @method \Aws\Result describeBackups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBackupsAsync(array $args = [])
 * @method \Aws\Result describeClusters(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeClustersAsync(array $args = [])
 * @method \Aws\Result initializeCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise initializeClusterAsync(array $args = [])
 * @method \Aws\Result listTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsAsync(array $args = [])
 * @method \Aws\Result modifyBackupAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise modifyBackupAttributesAsync(array $args = [])
 * @method \Aws\Result modifyCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise modifyClusterAsync(array $args = [])
 * @method \Aws\Result restoreBackup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise restoreBackupAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 */
class CloudHSMV2Client extends AwsClient {}
