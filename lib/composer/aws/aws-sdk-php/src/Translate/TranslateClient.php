<?php
namespace Aws\Translate;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Translate** service.
 * @method \Aws\Result createParallelData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createParallelDataAsync(array $args = [])
 * @method \Aws\Result deleteParallelData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteParallelDataAsync(array $args = [])
 * @method \Aws\Result deleteTerminology(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteTerminologyAsync(array $args = [])
 * @method \Aws\Result describeTextTranslationJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTextTranslationJobAsync(array $args = [])
 * @method \Aws\Result getParallelData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getParallelDataAsync(array $args = [])
 * @method \Aws\Result getTerminology(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTerminologyAsync(array $args = [])
 * @method \Aws\Result importTerminology(array $args = [])
 * @method \GuzzleHttp\Promise\Promise importTerminologyAsync(array $args = [])
 * @method \Aws\Result listParallelData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listParallelDataAsync(array $args = [])
 * @method \Aws\Result listTerminologies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTerminologiesAsync(array $args = [])
 * @method \Aws\Result listTextTranslationJobs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTextTranslationJobsAsync(array $args = [])
 * @method \Aws\Result startTextTranslationJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startTextTranslationJobAsync(array $args = [])
 * @method \Aws\Result stopTextTranslationJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopTextTranslationJobAsync(array $args = [])
 * @method \Aws\Result translateText(array $args = [])
 * @method \GuzzleHttp\Promise\Promise translateTextAsync(array $args = [])
 * @method \Aws\Result updateParallelData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateParallelDataAsync(array $args = [])
 */
class TranslateClient extends AwsClient {}
