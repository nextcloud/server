<?php
namespace Aws\KinesisAnalytics;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Kinesis Analytics** service.
 * @method \Aws\Result addApplicationCloudWatchLoggingOption(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addApplicationCloudWatchLoggingOptionAsync(array $args = [])
 * @method \Aws\Result addApplicationInput(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addApplicationInputAsync(array $args = [])
 * @method \Aws\Result addApplicationInputProcessingConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addApplicationInputProcessingConfigurationAsync(array $args = [])
 * @method \Aws\Result addApplicationOutput(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addApplicationOutputAsync(array $args = [])
 * @method \Aws\Result addApplicationReferenceDataSource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addApplicationReferenceDataSourceAsync(array $args = [])
 * @method \Aws\Result createApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createApplicationAsync(array $args = [])
 * @method \Aws\Result deleteApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteApplicationAsync(array $args = [])
 * @method \Aws\Result deleteApplicationCloudWatchLoggingOption(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteApplicationCloudWatchLoggingOptionAsync(array $args = [])
 * @method \Aws\Result deleteApplicationInputProcessingConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteApplicationInputProcessingConfigurationAsync(array $args = [])
 * @method \Aws\Result deleteApplicationOutput(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteApplicationOutputAsync(array $args = [])
 * @method \Aws\Result deleteApplicationReferenceDataSource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteApplicationReferenceDataSourceAsync(array $args = [])
 * @method \Aws\Result describeApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeApplicationAsync(array $args = [])
 * @method \Aws\Result discoverInputSchema(array $args = [])
 * @method \GuzzleHttp\Promise\Promise discoverInputSchemaAsync(array $args = [])
 * @method \Aws\Result listApplications(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listApplicationsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result startApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startApplicationAsync(array $args = [])
 * @method \Aws\Result stopApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopApplicationAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateApplicationAsync(array $args = [])
 */
class KinesisAnalyticsClient extends AwsClient {}
