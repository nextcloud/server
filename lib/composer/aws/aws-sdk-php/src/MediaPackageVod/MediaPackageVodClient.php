<?php
namespace Aws\MediaPackageVod;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Elemental MediaPackage VOD** service.
 * @method \Aws\Result configureLogs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise configureLogsAsync(array $args = [])
 * @method \Aws\Result createAsset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAssetAsync(array $args = [])
 * @method \Aws\Result createPackagingConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createPackagingConfigurationAsync(array $args = [])
 * @method \Aws\Result createPackagingGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createPackagingGroupAsync(array $args = [])
 * @method \Aws\Result deleteAsset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAssetAsync(array $args = [])
 * @method \Aws\Result deletePackagingConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deletePackagingConfigurationAsync(array $args = [])
 * @method \Aws\Result deletePackagingGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deletePackagingGroupAsync(array $args = [])
 * @method \Aws\Result describeAsset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAssetAsync(array $args = [])
 * @method \Aws\Result describePackagingConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describePackagingConfigurationAsync(array $args = [])
 * @method \Aws\Result describePackagingGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describePackagingGroupAsync(array $args = [])
 * @method \Aws\Result listAssets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAssetsAsync(array $args = [])
 * @method \Aws\Result listPackagingConfigurations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPackagingConfigurationsAsync(array $args = [])
 * @method \Aws\Result listPackagingGroups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPackagingGroupsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updatePackagingGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePackagingGroupAsync(array $args = [])
 */
class MediaPackageVodClient extends AwsClient {}
