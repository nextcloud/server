<?php
namespace Aws\SnowDeviceManagement;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Snow Device Management** service.
 * @method \Aws\Result cancelTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise cancelTaskAsync(array $args = [])
 * @method \Aws\Result createTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createTaskAsync(array $args = [])
 * @method \Aws\Result describeDevice(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDeviceAsync(array $args = [])
 * @method \Aws\Result describeDeviceEc2Instances(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDeviceEc2InstancesAsync(array $args = [])
 * @method \Aws\Result describeExecution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeExecutionAsync(array $args = [])
 * @method \Aws\Result describeTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTaskAsync(array $args = [])
 * @method \Aws\Result listDeviceResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDeviceResourcesAsync(array $args = [])
 * @method \Aws\Result listDevices(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDevicesAsync(array $args = [])
 * @method \Aws\Result listExecutions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listExecutionsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result listTasks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTasksAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 */
class SnowDeviceManagementClient extends AwsClient {}
