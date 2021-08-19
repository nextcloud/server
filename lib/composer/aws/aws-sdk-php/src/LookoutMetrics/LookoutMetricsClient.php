<?php
namespace Aws\LookoutMetrics;

use Aws\AwsClient;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

/**
 * This client is used to interact with the **Amazon Lookout for Metrics** service.
 * @method \Aws\Result activateAnomalyDetector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise activateAnomalyDetectorAsync(array $args = [])
 * @method \Aws\Result backTestAnomalyDetector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise backTestAnomalyDetectorAsync(array $args = [])
 * @method \Aws\Result createAlert(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAlertAsync(array $args = [])
 * @method \Aws\Result createAnomalyDetector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAnomalyDetectorAsync(array $args = [])
 * @method \Aws\Result createMetricSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createMetricSetAsync(array $args = [])
 * @method \Aws\Result deleteAlert(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAlertAsync(array $args = [])
 * @method \Aws\Result deleteAnomalyDetector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAnomalyDetectorAsync(array $args = [])
 * @method \Aws\Result describeAlert(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAlertAsync(array $args = [])
 * @method \Aws\Result describeAnomalyDetectionExecutions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAnomalyDetectionExecutionsAsync(array $args = [])
 * @method \Aws\Result describeAnomalyDetector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAnomalyDetectorAsync(array $args = [])
 * @method \Aws\Result describeMetricSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeMetricSetAsync(array $args = [])
 * @method \Aws\Result getAnomalyGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAnomalyGroupAsync(array $args = [])
 * @method \Aws\Result getFeedback(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFeedbackAsync(array $args = [])
 * @method \Aws\Result getSampleData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSampleDataAsync(array $args = [])
 * @method \Aws\Result listAlerts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAlertsAsync(array $args = [])
 * @method \Aws\Result listAnomalyDetectors(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAnomalyDetectorsAsync(array $args = [])
 * @method \Aws\Result listAnomalyGroupSummaries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAnomalyGroupSummariesAsync(array $args = [])
 * @method \Aws\Result listAnomalyGroupTimeSeries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAnomalyGroupTimeSeriesAsync(array $args = [])
 * @method \Aws\Result listMetricSets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listMetricSetsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result putFeedback(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putFeedbackAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateAnomalyDetector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateAnomalyDetectorAsync(array $args = [])
 * @method \Aws\Result updateMetricSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateMetricSetAsync(array $args = [])
 */
class LookoutMetricsClient extends AwsClient {
    public function __construct(array $args)
    {
        parent::__construct($args);

        // Setup middleware.
        $stack = $this->getHandlerList();
        $stack->appendBuild($this->updateContentType(), 'models.lookoutMetrics.v2.updateContentType');
    }

    /**
     * Creates a middleware that updates the Content-Type header when it is present;
     * this is necessary because the service protocol is rest-json which by default
     * sets the content-type to 'application/json', but interacting with the service
     * requires it to be set to x-amz-json-1.1
     *
     * @return callable
     */
    private function updateContentType()
    {
        return function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler) {
                $contentType = $request->getHeader('Content-Type');
                if (!empty($contentType) && $contentType[0] == 'application/json') {
                    return $handler($command, $request->withHeader(
                        'Content-Type',
                        'application/x-amz-json-1.1'
                    ));
                }
                return $handler($command, $request);
            };
        };
    }
}
