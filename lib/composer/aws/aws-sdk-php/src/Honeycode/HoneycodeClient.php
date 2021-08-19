<?php
namespace Aws\Honeycode;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Honeycode** service.
 * @method \Aws\Result batchCreateTableRows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchCreateTableRowsAsync(array $args = [])
 * @method \Aws\Result batchDeleteTableRows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchDeleteTableRowsAsync(array $args = [])
 * @method \Aws\Result batchUpdateTableRows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchUpdateTableRowsAsync(array $args = [])
 * @method \Aws\Result batchUpsertTableRows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchUpsertTableRowsAsync(array $args = [])
 * @method \Aws\Result describeTableDataImportJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTableDataImportJobAsync(array $args = [])
 * @method \Aws\Result getScreenData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getScreenDataAsync(array $args = [])
 * @method \Aws\Result invokeScreenAutomation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise invokeScreenAutomationAsync(array $args = [])
 * @method \Aws\Result listTableColumns(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTableColumnsAsync(array $args = [])
 * @method \Aws\Result listTableRows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTableRowsAsync(array $args = [])
 * @method \Aws\Result listTables(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTablesAsync(array $args = [])
 * @method \Aws\Result queryTableRows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise queryTableRowsAsync(array $args = [])
 * @method \Aws\Result startTableDataImportJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startTableDataImportJobAsync(array $args = [])
 */
class HoneycodeClient extends AwsClient {}
