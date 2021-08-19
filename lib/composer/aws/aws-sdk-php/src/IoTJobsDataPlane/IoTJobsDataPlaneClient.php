<?php
namespace Aws\IoTJobsDataPlane;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS IoT Jobs Data Plane** service.
 * @method \Aws\Result describeJobExecution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeJobExecutionAsync(array $args = [])
 * @method \Aws\Result getPendingJobExecutions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getPendingJobExecutionsAsync(array $args = [])
 * @method \Aws\Result startNextPendingJobExecution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startNextPendingJobExecutionAsync(array $args = [])
 * @method \Aws\Result updateJobExecution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateJobExecutionAsync(array $args = [])
 */
class IoTJobsDataPlaneClient extends AwsClient {}
