<?php
namespace Aws\DynamoDb;

use Aws\Api\Parser\Crc32ValidatingParser;
use Aws\AwsClient;
use Aws\ClientResolver;
use Aws\Exception\AwsException;
use Aws\HandlerList;
use Aws\Middleware;
use Aws\RetryMiddleware;
use Aws\RetryMiddlewareV2;

/**
 * This client is used to interact with the **Amazon DynamoDB** service.
 *
 * @method \Aws\Result batchGetItem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchGetItemAsync(array $args = [])
 * @method \Aws\Result batchWriteItem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchWriteItemAsync(array $args = [])
 * @method \Aws\Result createTable(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createTableAsync(array $args = [])
 * @method \Aws\Result deleteItem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteItemAsync(array $args = [])
 * @method \Aws\Result deleteTable(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteTableAsync(array $args = [])
 * @method \Aws\Result describeTable(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTableAsync(array $args = [])
 * @method \Aws\Result getItem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getItemAsync(array $args = [])
 * @method \Aws\Result listTables(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTablesAsync(array $args = [])
 * @method \Aws\Result putItem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putItemAsync(array $args = [])
 * @method \Aws\Result query(array $args = [])
 * @method \GuzzleHttp\Promise\Promise queryAsync(array $args = [])
 * @method \Aws\Result scan(array $args = [])
 * @method \GuzzleHttp\Promise\Promise scanAsync(array $args = [])
 * @method \Aws\Result updateItem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateItemAsync(array $args = [])
 * @method \Aws\Result updateTable(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateTableAsync(array $args = [])
 * @method \Aws\Result batchExecuteStatement(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise batchExecuteStatementAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result createBackup(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise createBackupAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result createGlobalTable(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise createGlobalTableAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result deleteBackup(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise deleteBackupAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeBackup(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeBackupAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeContinuousBackups(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeContinuousBackupsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeContributorInsights(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeContributorInsightsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeEndpoints(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeEndpointsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeExport(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeExportAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeGlobalTable(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeGlobalTableAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeGlobalTableSettings(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeGlobalTableSettingsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeKinesisStreamingDestination(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeKinesisStreamingDestinationAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeLimits(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeLimitsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeTableReplicaAutoScaling(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeTableReplicaAutoScalingAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result describeTimeToLive(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise describeTimeToLiveAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result disableKinesisStreamingDestination(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise disableKinesisStreamingDestinationAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result enableKinesisStreamingDestination(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise enableKinesisStreamingDestinationAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result executeStatement(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise executeStatementAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result executeTransaction(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise executeTransactionAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result exportTableToPointInTime(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise exportTableToPointInTimeAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result listBackups(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise listBackupsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result listContributorInsights(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise listContributorInsightsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result listExports(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise listExportsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result listGlobalTables(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise listGlobalTablesAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result listTagsOfResource(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise listTagsOfResourceAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result restoreTableFromBackup(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise restoreTableFromBackupAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result restoreTableToPointInTime(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise restoreTableToPointInTimeAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result tagResource(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result transactGetItems(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise transactGetItemsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result transactWriteItems(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise transactWriteItemsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result untagResource(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result updateContinuousBackups(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise updateContinuousBackupsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result updateContributorInsights(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise updateContributorInsightsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result updateGlobalTable(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise updateGlobalTableAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result updateGlobalTableSettings(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise updateGlobalTableSettingsAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result updateTableReplicaAutoScaling(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise updateTableReplicaAutoScalingAsync(array $args = []) (supported in versions 2012-08-10)
 * @method \Aws\Result updateTimeToLive(array $args = []) (supported in versions 2012-08-10)
 * @method \GuzzleHttp\Promise\Promise updateTimeToLiveAsync(array $args = []) (supported in versions 2012-08-10)
 */
class DynamoDbClient extends AwsClient
{
    public static function getArguments()
    {
        $args = parent::getArguments();
        $args['retries']['default'] = 10;
        $args['retries']['fn'] = [__CLASS__, '_applyRetryConfig'];
        $args['api_provider']['fn'] = [__CLASS__, '_applyApiProvider'];

        return $args;
    }

    /**
     * Convenience method for instantiating and registering the DynamoDB
     * Session handler with this DynamoDB client object.
     *
     * @param array $config Array of options for the session handler factory
     *
     * @return SessionHandler
     */
    public function registerSessionHandler(array $config = [])
    {
        $handler = SessionHandler::fromClient($this, $config);
        $handler->register();

        return $handler;
    }

    /** @internal */
    public static function _applyRetryConfig($value, array &$args, HandlerList $list)
    {
        if ($value) {
            $config = \Aws\Retry\ConfigurationProvider::unwrap($value);

            if ($config->getMode() === 'legacy') {
                $list->appendSign(
                    Middleware::retry(
                        RetryMiddleware::createDefaultDecider(
                            $config->getMaxAttempts() - 1,
                            ['error_codes' => ['TransactionInProgressException']]
                        ),
                        function ($retries) {
                            return $retries
                                ? RetryMiddleware::exponentialDelay($retries) / 2
                                : 0;
                        },
                        isset($args['stats']['retries'])
                            ? (bool)$args['stats']['retries']
                            : false
                    ),
                    'retry'
                );
            } else {
                $list->appendSign(
                    RetryMiddlewareV2::wrap(
                        $config,
                        [
                            'collect_stats' => $args['stats']['retries'],
                            'transient_error_codes' => ['TransactionInProgressException']
                        ]
                    ),
                    'retry'
                );
            }
        }
    }

    /** @internal */
    public static function _applyApiProvider($value, array &$args, HandlerList $list)
    {
        ClientResolver::_apply_api_provider($value, $args);
        $args['parser'] = new Crc32ValidatingParser($args['parser']);
    }
}
