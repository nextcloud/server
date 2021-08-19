<?php
namespace Aws\RedshiftDataAPIService;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Redshift Data API Service** service.
 * @method \Aws\Result batchExecuteStatement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchExecuteStatementAsync(array $args = [])
 * @method \Aws\Result cancelStatement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise cancelStatementAsync(array $args = [])
 * @method \Aws\Result describeStatement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeStatementAsync(array $args = [])
 * @method \Aws\Result describeTable(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTableAsync(array $args = [])
 * @method \Aws\Result executeStatement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise executeStatementAsync(array $args = [])
 * @method \Aws\Result getStatementResult(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getStatementResultAsync(array $args = [])
 * @method \Aws\Result listDatabases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDatabasesAsync(array $args = [])
 * @method \Aws\Result listSchemas(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSchemasAsync(array $args = [])
 * @method \Aws\Result listStatements(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listStatementsAsync(array $args = [])
 * @method \Aws\Result listTables(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTablesAsync(array $args = [])
 */
class RedshiftDataAPIServiceClient extends AwsClient {}
