<?php
namespace Aws\LookoutforVision;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Lookout for Vision** service.
 * @method \Aws\Result createDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDatasetAsync(array $args = [])
 * @method \Aws\Result createModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createModelAsync(array $args = [])
 * @method \Aws\Result createProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createProjectAsync(array $args = [])
 * @method \Aws\Result deleteDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteDatasetAsync(array $args = [])
 * @method \Aws\Result deleteModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteModelAsync(array $args = [])
 * @method \Aws\Result deleteProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteProjectAsync(array $args = [])
 * @method \Aws\Result describeDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDatasetAsync(array $args = [])
 * @method \Aws\Result describeModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeModelAsync(array $args = [])
 * @method \Aws\Result describeProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeProjectAsync(array $args = [])
 * @method \Aws\Result detectAnomalies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise detectAnomaliesAsync(array $args = [])
 * @method \Aws\Result listDatasetEntries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDatasetEntriesAsync(array $args = [])
 * @method \Aws\Result listModels(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listModelsAsync(array $args = [])
 * @method \Aws\Result listProjects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listProjectsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result startModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startModelAsync(array $args = [])
 * @method \Aws\Result stopModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopModelAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateDatasetEntries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateDatasetEntriesAsync(array $args = [])
 */
class LookoutforVisionClient extends AwsClient {}
