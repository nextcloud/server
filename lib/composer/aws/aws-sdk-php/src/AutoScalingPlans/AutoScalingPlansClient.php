<?php
namespace Aws\AutoScalingPlans;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Auto Scaling Plans** service.
 * @method \Aws\Result createScalingPlan(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createScalingPlanAsync(array $args = [])
 * @method \Aws\Result deleteScalingPlan(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteScalingPlanAsync(array $args = [])
 * @method \Aws\Result describeScalingPlanResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeScalingPlanResourcesAsync(array $args = [])
 * @method \Aws\Result describeScalingPlans(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeScalingPlansAsync(array $args = [])
 * @method \Aws\Result getScalingPlanResourceForecastData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getScalingPlanResourceForecastDataAsync(array $args = [])
 * @method \Aws\Result updateScalingPlan(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateScalingPlanAsync(array $args = [])
 */
class AutoScalingPlansClient extends AwsClient {}
