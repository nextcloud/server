<?php
namespace Aws\PI;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Performance Insights** service.
 * @method \Aws\Result describeDimensionKeys(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDimensionKeysAsync(array $args = [])
 * @method \Aws\Result getDimensionKeyDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDimensionKeyDetailsAsync(array $args = [])
 * @method \Aws\Result getResourceMetrics(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getResourceMetricsAsync(array $args = [])
 */
class PIClient extends AwsClient {}
