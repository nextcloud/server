<?php
namespace Aws\Appflow;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Appflow** service.
 * @method \Aws\Result createConnectorProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createConnectorProfileAsync(array $args = [])
 * @method \Aws\Result createFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createFlowAsync(array $args = [])
 * @method \Aws\Result deleteConnectorProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteConnectorProfileAsync(array $args = [])
 * @method \Aws\Result deleteFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteFlowAsync(array $args = [])
 * @method \Aws\Result describeConnectorEntity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConnectorEntityAsync(array $args = [])
 * @method \Aws\Result describeConnectorProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConnectorProfilesAsync(array $args = [])
 * @method \Aws\Result describeConnectors(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConnectorsAsync(array $args = [])
 * @method \Aws\Result describeFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeFlowAsync(array $args = [])
 * @method \Aws\Result describeFlowExecutionRecords(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeFlowExecutionRecordsAsync(array $args = [])
 * @method \Aws\Result listConnectorEntities(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listConnectorEntitiesAsync(array $args = [])
 * @method \Aws\Result listFlows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listFlowsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result startFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startFlowAsync(array $args = [])
 * @method \Aws\Result stopFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopFlowAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateConnectorProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateConnectorProfileAsync(array $args = [])
 * @method \Aws\Result updateFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateFlowAsync(array $args = [])
 */
class AppflowClient extends AwsClient {}
