<?php
namespace Aws\MarketplaceMetering;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWSMarketplace Metering** service.
 * @method \Aws\Result batchMeterUsage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchMeterUsageAsync(array $args = [])
 * @method \Aws\Result meterUsage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise meterUsageAsync(array $args = [])
 * @method \Aws\Result registerUsage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise registerUsageAsync(array $args = [])
 * @method \Aws\Result resolveCustomer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise resolveCustomerAsync(array $args = [])
 */
class MarketplaceMeteringClient extends AwsClient {}
