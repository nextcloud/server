<?php
namespace Aws\MediaPackage;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Elemental MediaPackage** service.
 * @method \Aws\Result configureLogs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise configureLogsAsync(array $args = [])
 * @method \Aws\Result createChannel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createChannelAsync(array $args = [])
 * @method \Aws\Result createHarvestJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHarvestJobAsync(array $args = [])
 * @method \Aws\Result createOriginEndpoint(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createOriginEndpointAsync(array $args = [])
 * @method \Aws\Result deleteChannel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteChannelAsync(array $args = [])
 * @method \Aws\Result deleteOriginEndpoint(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteOriginEndpointAsync(array $args = [])
 * @method \Aws\Result describeChannel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeChannelAsync(array $args = [])
 * @method \Aws\Result describeHarvestJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeHarvestJobAsync(array $args = [])
 * @method \Aws\Result describeOriginEndpoint(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeOriginEndpointAsync(array $args = [])
 * @method \Aws\Result listChannels(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listChannelsAsync(array $args = [])
 * @method \Aws\Result listHarvestJobs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHarvestJobsAsync(array $args = [])
 * @method \Aws\Result listOriginEndpoints(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listOriginEndpointsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result rotateChannelCredentials(array $args = [])
 * @method \GuzzleHttp\Promise\Promise rotateChannelCredentialsAsync(array $args = [])
 * @method \Aws\Result rotateIngestEndpointCredentials(array $args = [])
 * @method \GuzzleHttp\Promise\Promise rotateIngestEndpointCredentialsAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateChannel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateChannelAsync(array $args = [])
 * @method \Aws\Result updateOriginEndpoint(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateOriginEndpointAsync(array $args = [])
 */
class MediaPackageClient extends AwsClient {}
