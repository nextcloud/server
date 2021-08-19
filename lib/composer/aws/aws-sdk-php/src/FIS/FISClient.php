<?php
namespace Aws\FIS;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Fault Injection Simulator** service.
 * @method \Aws\Result createExperimentTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createExperimentTemplateAsync(array $args = [])
 * @method \Aws\Result deleteExperimentTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteExperimentTemplateAsync(array $args = [])
 * @method \Aws\Result getAction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getActionAsync(array $args = [])
 * @method \Aws\Result getExperiment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getExperimentAsync(array $args = [])
 * @method \Aws\Result getExperimentTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getExperimentTemplateAsync(array $args = [])
 * @method \Aws\Result listActions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listActionsAsync(array $args = [])
 * @method \Aws\Result listExperimentTemplates(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listExperimentTemplatesAsync(array $args = [])
 * @method \Aws\Result listExperiments(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listExperimentsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result startExperiment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startExperimentAsync(array $args = [])
 * @method \Aws\Result stopExperiment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopExperimentAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateExperimentTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateExperimentTemplateAsync(array $args = [])
 */
class FISClient extends AwsClient {}
