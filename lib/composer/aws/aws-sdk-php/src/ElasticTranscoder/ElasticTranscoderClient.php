<?php
namespace Aws\ElasticTranscoder;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Elastic Transcoder** service.
 *
 * @method \Aws\Result cancelJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise cancelJobAsync(array $args = [])
 * @method \Aws\Result createJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createJobAsync(array $args = [])
 * @method \Aws\Result createPipeline(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createPipelineAsync(array $args = [])
 * @method \Aws\Result createPreset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createPresetAsync(array $args = [])
 * @method \Aws\Result deletePipeline(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deletePipelineAsync(array $args = [])
 * @method \Aws\Result deletePreset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deletePresetAsync(array $args = [])
 * @method \Aws\Result listJobsByPipeline(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listJobsByPipelineAsync(array $args = [])
 * @method \Aws\Result listJobsByStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listJobsByStatusAsync(array $args = [])
 * @method \Aws\Result listPipelines(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPipelinesAsync(array $args = [])
 * @method \Aws\Result listPresets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPresetsAsync(array $args = [])
 * @method \Aws\Result readJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise readJobAsync(array $args = [])
 * @method \Aws\Result readPipeline(array $args = [])
 * @method \GuzzleHttp\Promise\Promise readPipelineAsync(array $args = [])
 * @method \Aws\Result readPreset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise readPresetAsync(array $args = [])
 * @method \Aws\Result testRole(array $args = [])
 * @method \GuzzleHttp\Promise\Promise testRoleAsync(array $args = [])
 * @method \Aws\Result updatePipeline(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePipelineAsync(array $args = [])
 * @method \Aws\Result updatePipelineNotifications(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePipelineNotificationsAsync(array $args = [])
 * @method \Aws\Result updatePipelineStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePipelineStatusAsync(array $args = [])
 */
class ElasticTranscoderClient extends AwsClient {}
