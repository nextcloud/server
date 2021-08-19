<?php
namespace Aws\IoTDeviceAdvisor;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS IoT Core Device Advisor** service.
 * @method \Aws\Result createSuiteDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createSuiteDefinitionAsync(array $args = [])
 * @method \Aws\Result deleteSuiteDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteSuiteDefinitionAsync(array $args = [])
 * @method \Aws\Result getSuiteDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSuiteDefinitionAsync(array $args = [])
 * @method \Aws\Result getSuiteRun(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSuiteRunAsync(array $args = [])
 * @method \Aws\Result getSuiteRunReport(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSuiteRunReportAsync(array $args = [])
 * @method \Aws\Result listSuiteDefinitions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSuiteDefinitionsAsync(array $args = [])
 * @method \Aws\Result listSuiteRuns(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSuiteRunsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result startSuiteRun(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startSuiteRunAsync(array $args = [])
 * @method \Aws\Result stopSuiteRun(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopSuiteRunAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateSuiteDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateSuiteDefinitionAsync(array $args = [])
 */
class IoTDeviceAdvisorClient extends AwsClient {}
