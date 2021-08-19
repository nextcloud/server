<?php
namespace Aws\AugmentedAIRuntime;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Augmented AI Runtime** service.
 * @method \Aws\Result deleteHumanLoop(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHumanLoopAsync(array $args = [])
 * @method \Aws\Result describeHumanLoop(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeHumanLoopAsync(array $args = [])
 * @method \Aws\Result listHumanLoops(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHumanLoopsAsync(array $args = [])
 * @method \Aws\Result startHumanLoop(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startHumanLoopAsync(array $args = [])
 * @method \Aws\Result stopHumanLoop(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopHumanLoopAsync(array $args = [])
 */
class AugmentedAIRuntimeClient extends AwsClient {}
