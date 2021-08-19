<?php
namespace Aws\TimestreamQuery;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Timestream Query** service.
 * @method \Aws\Result cancelQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise cancelQueryAsync(array $args = [])
 * @method \Aws\Result describeEndpoints(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEndpointsAsync(array $args = [])
 * @method \Aws\Result query(array $args = [])
 * @method \GuzzleHttp\Promise\Promise queryAsync(array $args = [])
 */
class TimestreamQueryClient extends AwsClient {}
