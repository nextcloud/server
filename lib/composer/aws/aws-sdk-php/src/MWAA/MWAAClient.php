<?php
namespace Aws\MWAA;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AmazonMWAA** service.
 * @method \Aws\Result createCliToken(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createCliTokenAsync(array $args = [])
 * @method \Aws\Result createEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEnvironmentAsync(array $args = [])
 * @method \Aws\Result createWebLoginToken(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createWebLoginTokenAsync(array $args = [])
 * @method \Aws\Result deleteEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEnvironmentAsync(array $args = [])
 * @method \Aws\Result getEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEnvironmentAsync(array $args = [])
 * @method \Aws\Result listEnvironments(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEnvironmentsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result publishMetrics(array $args = [])
 * @method \GuzzleHttp\Promise\Promise publishMetricsAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEnvironmentAsync(array $args = [])
 */
class MWAAClient extends AwsClient {}
