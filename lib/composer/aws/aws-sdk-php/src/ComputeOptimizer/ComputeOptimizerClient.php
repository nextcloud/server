<?php
namespace Aws\ComputeOptimizer;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Compute Optimizer** service.
 * @method \Aws\Result describeRecommendationExportJobs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRecommendationExportJobsAsync(array $args = [])
 * @method \Aws\Result exportAutoScalingGroupRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportAutoScalingGroupRecommendationsAsync(array $args = [])
 * @method \Aws\Result exportEBSVolumeRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportEBSVolumeRecommendationsAsync(array $args = [])
 * @method \Aws\Result exportEC2InstanceRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportEC2InstanceRecommendationsAsync(array $args = [])
 * @method \Aws\Result exportLambdaFunctionRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportLambdaFunctionRecommendationsAsync(array $args = [])
 * @method \Aws\Result getAutoScalingGroupRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAutoScalingGroupRecommendationsAsync(array $args = [])
 * @method \Aws\Result getEBSVolumeRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEBSVolumeRecommendationsAsync(array $args = [])
 * @method \Aws\Result getEC2InstanceRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEC2InstanceRecommendationsAsync(array $args = [])
 * @method \Aws\Result getEC2RecommendationProjectedMetrics(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEC2RecommendationProjectedMetricsAsync(array $args = [])
 * @method \Aws\Result getEnrollmentStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEnrollmentStatusAsync(array $args = [])
 * @method \Aws\Result getLambdaFunctionRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getLambdaFunctionRecommendationsAsync(array $args = [])
 * @method \Aws\Result getRecommendationSummaries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getRecommendationSummariesAsync(array $args = [])
 * @method \Aws\Result updateEnrollmentStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEnrollmentStatusAsync(array $args = [])
 */
class ComputeOptimizerClient extends AwsClient {}
