<?php
namespace Aws\MachineLearning;

use Aws\AwsClient;
use Aws\CommandInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

/**
 * Amazon Machine Learning client.
 *
 * @method \Aws\Result addTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addTagsAsync(array $args = [])
 * @method \Aws\Result createBatchPrediction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createBatchPredictionAsync(array $args = [])
 * @method \Aws\Result createDataSourceFromRDS(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDataSourceFromRDSAsync(array $args = [])
 * @method \Aws\Result createDataSourceFromRedshift(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDataSourceFromRedshiftAsync(array $args = [])
 * @method \Aws\Result createDataSourceFromS3(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDataSourceFromS3Async(array $args = [])
 * @method \Aws\Result createEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEvaluationAsync(array $args = [])
 * @method \Aws\Result createMLModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createMLModelAsync(array $args = [])
 * @method \Aws\Result createRealtimeEndpoint(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createRealtimeEndpointAsync(array $args = [])
 * @method \Aws\Result deleteBatchPrediction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteBatchPredictionAsync(array $args = [])
 * @method \Aws\Result deleteDataSource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteDataSourceAsync(array $args = [])
 * @method \Aws\Result deleteEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEvaluationAsync(array $args = [])
 * @method \Aws\Result deleteMLModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteMLModelAsync(array $args = [])
 * @method \Aws\Result deleteRealtimeEndpoint(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteRealtimeEndpointAsync(array $args = [])
 * @method \Aws\Result deleteTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteTagsAsync(array $args = [])
 * @method \Aws\Result describeBatchPredictions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBatchPredictionsAsync(array $args = [])
 * @method \Aws\Result describeDataSources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDataSourcesAsync(array $args = [])
 * @method \Aws\Result describeEvaluations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEvaluationsAsync(array $args = [])
 * @method \Aws\Result describeMLModels(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeMLModelsAsync(array $args = [])
 * @method \Aws\Result describeTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTagsAsync(array $args = [])
 * @method \Aws\Result getBatchPrediction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getBatchPredictionAsync(array $args = [])
 * @method \Aws\Result getDataSource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDataSourceAsync(array $args = [])
 * @method \Aws\Result getEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEvaluationAsync(array $args = [])
 * @method \Aws\Result getMLModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getMLModelAsync(array $args = [])
 * @method \Aws\Result predict(array $args = [])
 * @method \GuzzleHttp\Promise\Promise predictAsync(array $args = [])
 * @method \Aws\Result updateBatchPrediction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateBatchPredictionAsync(array $args = [])
 * @method \Aws\Result updateDataSource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateDataSourceAsync(array $args = [])
 * @method \Aws\Result updateEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEvaluationAsync(array $args = [])
 * @method \Aws\Result updateMLModel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateMLModelAsync(array $args = [])
 */
class MachineLearningClient extends AwsClient
{
    public function __construct(array $config)
    {
        parent::__construct($config);
        $list = $this->getHandlerList();
        $list->appendBuild($this->predictEndpoint(), 'ml.predict_endpoint');
    }

    /**
     * Changes the endpoint of the Predict operation to the provided endpoint.
     *
     * @return callable
     */
    private function predictEndpoint()
    {
        return static function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler) {
                if ($command->getName() === 'Predict') {
                    $request = $request->withUri(new Uri($command['PredictEndpoint']));
                }
                return $handler($command, $request);
            };
        };
    }
}
