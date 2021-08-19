<?php
namespace Aws\CloudHsm;

use Aws\Api\ApiProvider;
use Aws\Api\DocModel;
use Aws\Api\Service;
use Aws\AwsClient;

/**
 * This client is used to interact with **AWS CloudHSM**.
 *
 * @method \Aws\Result addTagsToResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addTagsToResourceAsync(array $args = [])
 * @method \Aws\Result createHapg(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHapgAsync(array $args = [])
 * @method \Aws\Result createHsm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHsmAsync(array $args = [])
 * @method \Aws\Result createLunaClient(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createLunaClientAsync(array $args = [])
 * @method \Aws\Result deleteHapg(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHapgAsync(array $args = [])
 * @method \Aws\Result deleteHsm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHsmAsync(array $args = [])
 * @method \Aws\Result deleteLunaClient(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteLunaClientAsync(array $args = [])
 * @method \Aws\Result describeHapg(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeHapgAsync(array $args = [])
 * @method \Aws\Result describeHsm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeHsmAsync(array $args = [])
 * @method \Aws\Result describeLunaClient(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeLunaClientAsync(array $args = [])
 * @method \Aws\Result getConfigFiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getConfigFilesAsync(array $args = [])
 * @method \Aws\Result listAvailableZones(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAvailableZonesAsync(array $args = [])
 * @method \Aws\Result listHapgs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHapgsAsync(array $args = [])
 * @method \Aws\Result listHsms(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHsmsAsync(array $args = [])
 * @method \Aws\Result listLunaClients(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listLunaClientsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result modifyHapg(array $args = [])
 * @method \GuzzleHttp\Promise\Promise modifyHapgAsync(array $args = [])
 * @method \Aws\Result modifyHsm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise modifyHsmAsync(array $args = [])
 * @method \Aws\Result modifyLunaClient(array $args = [])
 * @method \GuzzleHttp\Promise\Promise modifyLunaClientAsync(array $args = [])
 * @method \Aws\Result removeTagsFromResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise removeTagsFromResourceAsync(array $args = [])
 */
class CloudHsmClient extends AwsClient {}
