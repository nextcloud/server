<?php
namespace Aws\IoT1ClickDevicesService;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS IoT 1-Click Devices Service** service.
 * @method \Aws\Result claimDevicesByClaimCode(array $args = [])
 * @method \GuzzleHttp\Promise\Promise claimDevicesByClaimCodeAsync(array $args = [])
 * @method \Aws\Result describeDevice(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDeviceAsync(array $args = [])
 * @method \Aws\Result finalizeDeviceClaim(array $args = [])
 * @method \GuzzleHttp\Promise\Promise finalizeDeviceClaimAsync(array $args = [])
 * @method \Aws\Result getDeviceMethods(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDeviceMethodsAsync(array $args = [])
 * @method \Aws\Result initiateDeviceClaim(array $args = [])
 * @method \GuzzleHttp\Promise\Promise initiateDeviceClaimAsync(array $args = [])
 * @method \Aws\Result invokeDeviceMethod(array $args = [])
 * @method \GuzzleHttp\Promise\Promise invokeDeviceMethodAsync(array $args = [])
 * @method \Aws\Result listDeviceEvents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDeviceEventsAsync(array $args = [])
 * @method \Aws\Result listDevices(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDevicesAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result unclaimDevice(array $args = [])
 * @method \GuzzleHttp\Promise\Promise unclaimDeviceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateDeviceState(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateDeviceStateAsync(array $args = [])
 */
class IoT1ClickDevicesServiceClient extends AwsClient {}
