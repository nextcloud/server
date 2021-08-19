<?php
namespace Aws\MediaStoreData;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Elemental MediaStore Data Plane** service.
 * @method \Aws\Result deleteObject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteObjectAsync(array $args = [])
 * @method \Aws\Result describeObject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeObjectAsync(array $args = [])
 * @method \Aws\Result getObject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getObjectAsync(array $args = [])
 * @method \Aws\Result listItems(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listItemsAsync(array $args = [])
 * @method \Aws\Result putObject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putObjectAsync(array $args = [])
 */
class MediaStoreDataClient extends AwsClient {}
