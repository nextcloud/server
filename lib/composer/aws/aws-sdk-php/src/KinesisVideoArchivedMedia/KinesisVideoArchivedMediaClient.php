<?php
namespace Aws\KinesisVideoArchivedMedia;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Kinesis Video Streams Archived Media** service.
 * @method \Aws\Result getClip(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getClipAsync(array $args = [])
 * @method \Aws\Result getDASHStreamingSessionURL(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDASHStreamingSessionURLAsync(array $args = [])
 * @method \Aws\Result getHLSStreamingSessionURL(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHLSStreamingSessionURLAsync(array $args = [])
 * @method \Aws\Result getMediaForFragmentList(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getMediaForFragmentListAsync(array $args = [])
 * @method \Aws\Result listFragments(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listFragmentsAsync(array $args = [])
 */
class KinesisVideoArchivedMediaClient extends AwsClient {}
