<?php
namespace Aws\DLM;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Data Lifecycle Manager** service.
 * @method \Aws\Result createLifecyclePolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createLifecyclePolicyAsync(array $args = [])
 * @method \Aws\Result deleteLifecyclePolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteLifecyclePolicyAsync(array $args = [])
 * @method \Aws\Result getLifecyclePolicies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getLifecyclePoliciesAsync(array $args = [])
 * @method \Aws\Result getLifecyclePolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getLifecyclePolicyAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateLifecyclePolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateLifecyclePolicyAsync(array $args = [])
 */
class DLMClient extends AwsClient {}
