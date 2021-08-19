<?php
namespace Aws\Pricing;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Price List Service** service.
 * @method \Aws\Result describeServices(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeServicesAsync(array $args = [])
 * @method \Aws\Result getAttributeValues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAttributeValuesAsync(array $args = [])
 * @method \Aws\Result getProducts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getProductsAsync(array $args = [])
 */
class PricingClient extends AwsClient {}
