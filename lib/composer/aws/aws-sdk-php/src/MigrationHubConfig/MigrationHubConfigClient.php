<?php
namespace Aws\MigrationHubConfig;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Migration Hub Config** service.
 * @method \Aws\Result createHomeRegionControl(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHomeRegionControlAsync(array $args = [])
 * @method \Aws\Result describeHomeRegionControls(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeHomeRegionControlsAsync(array $args = [])
 * @method \Aws\Result getHomeRegion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHomeRegionAsync(array $args = [])
 */
class MigrationHubConfigClient extends AwsClient {}
