<?php
namespace Aws\ImportExport;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Import/Export** service.
 * @method \Aws\Result cancelJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise cancelJobAsync(array $args = [])
 * @method \Aws\Result createJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createJobAsync(array $args = [])
 * @method \Aws\Result getShippingLabel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getShippingLabelAsync(array $args = [])
 * @method \Aws\Result getStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getStatusAsync(array $args = [])
 * @method \Aws\Result listJobs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listJobsAsync(array $args = [])
 * @method \Aws\Result updateJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateJobAsync(array $args = [])
 */
class ImportExportClient extends AwsClient {}
