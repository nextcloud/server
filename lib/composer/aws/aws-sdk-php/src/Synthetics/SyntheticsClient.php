<?php
namespace Aws\Synthetics;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Synthetics** service.
 * @method \Aws\Result createCanary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createCanaryAsync(array $args = [])
 * @method \Aws\Result deleteCanary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteCanaryAsync(array $args = [])
 * @method \Aws\Result describeCanaries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeCanariesAsync(array $args = [])
 * @method \Aws\Result describeCanariesLastRun(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeCanariesLastRunAsync(array $args = [])
 * @method \Aws\Result describeRuntimeVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRuntimeVersionsAsync(array $args = [])
 * @method \Aws\Result getCanary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCanaryAsync(array $args = [])
 * @method \Aws\Result getCanaryRuns(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCanaryRunsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result startCanary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startCanaryAsync(array $args = [])
 * @method \Aws\Result stopCanary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopCanaryAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateCanary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateCanaryAsync(array $args = [])
 */
class SyntheticsClient extends AwsClient {}
