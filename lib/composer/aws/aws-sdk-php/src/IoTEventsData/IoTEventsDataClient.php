<?php
namespace Aws\IoTEventsData;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS IoT Events Data** service.
 * @method \Aws\Result batchAcknowledgeAlarm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchAcknowledgeAlarmAsync(array $args = [])
 * @method \Aws\Result batchDisableAlarm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchDisableAlarmAsync(array $args = [])
 * @method \Aws\Result batchEnableAlarm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchEnableAlarmAsync(array $args = [])
 * @method \Aws\Result batchPutMessage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchPutMessageAsync(array $args = [])
 * @method \Aws\Result batchResetAlarm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchResetAlarmAsync(array $args = [])
 * @method \Aws\Result batchSnoozeAlarm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchSnoozeAlarmAsync(array $args = [])
 * @method \Aws\Result batchUpdateDetector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchUpdateDetectorAsync(array $args = [])
 * @method \Aws\Result describeAlarm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAlarmAsync(array $args = [])
 * @method \Aws\Result describeDetector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDetectorAsync(array $args = [])
 * @method \Aws\Result listAlarms(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAlarmsAsync(array $args = [])
 * @method \Aws\Result listDetectors(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDetectorsAsync(array $args = [])
 */
class IoTEventsDataClient extends AwsClient {}
