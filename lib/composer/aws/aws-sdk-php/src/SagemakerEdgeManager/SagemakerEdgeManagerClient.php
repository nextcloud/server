<?php
namespace Aws\SagemakerEdgeManager;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Sagemaker Edge Manager** service.
 * @method \Aws\Result getDeviceRegistration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDeviceRegistrationAsync(array $args = [])
 * @method \Aws\Result sendHeartbeat(array $args = [])
 * @method \GuzzleHttp\Promise\Promise sendHeartbeatAsync(array $args = [])
 */
class SagemakerEdgeManagerClient extends AwsClient {}
