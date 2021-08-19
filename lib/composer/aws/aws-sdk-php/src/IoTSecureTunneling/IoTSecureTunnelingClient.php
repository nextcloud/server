<?php
namespace Aws\IoTSecureTunneling;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS IoT Secure Tunneling** service.
 * @method \Aws\Result closeTunnel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise closeTunnelAsync(array $args = [])
 * @method \Aws\Result describeTunnel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTunnelAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result listTunnels(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTunnelsAsync(array $args = [])
 * @method \Aws\Result openTunnel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise openTunnelAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 */
class IoTSecureTunnelingClient extends AwsClient {}
