<?php
namespace Aws\Cloud9;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Cloud9** service.
 * @method \Aws\Result createEnvironmentEC2(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEnvironmentEC2Async(array $args = [])
 * @method \Aws\Result createEnvironmentMembership(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEnvironmentMembershipAsync(array $args = [])
 * @method \Aws\Result deleteEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEnvironmentAsync(array $args = [])
 * @method \Aws\Result deleteEnvironmentMembership(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEnvironmentMembershipAsync(array $args = [])
 * @method \Aws\Result describeEnvironmentMemberships(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEnvironmentMembershipsAsync(array $args = [])
 * @method \Aws\Result describeEnvironmentStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEnvironmentStatusAsync(array $args = [])
 * @method \Aws\Result describeEnvironments(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEnvironmentsAsync(array $args = [])
 * @method \Aws\Result listEnvironments(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEnvironmentsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateEnvironment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEnvironmentAsync(array $args = [])
 * @method \Aws\Result updateEnvironmentMembership(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEnvironmentMembershipAsync(array $args = [])
 */
class Cloud9Client extends AwsClient {}
