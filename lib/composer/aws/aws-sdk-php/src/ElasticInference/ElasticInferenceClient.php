<?php
namespace Aws\ElasticInference;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Elastic  Inference** service.
 * @method \Aws\Result describeAcceleratorOfferings(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAcceleratorOfferingsAsync(array $args = [])
 * @method \Aws\Result describeAcceleratorTypes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAcceleratorTypesAsync(array $args = [])
 * @method \Aws\Result describeAccelerators(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAcceleratorsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 */
class ElasticInferenceClient extends AwsClient {}
