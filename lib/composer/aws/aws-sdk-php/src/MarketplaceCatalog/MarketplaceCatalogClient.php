<?php
namespace Aws\MarketplaceCatalog;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Marketplace Catalog Service** service.
 * @method \Aws\Result cancelChangeSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise cancelChangeSetAsync(array $args = [])
 * @method \Aws\Result describeChangeSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeChangeSetAsync(array $args = [])
 * @method \Aws\Result describeEntity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEntityAsync(array $args = [])
 * @method \Aws\Result listChangeSets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listChangeSetsAsync(array $args = [])
 * @method \Aws\Result listEntities(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEntitiesAsync(array $args = [])
 * @method \Aws\Result startChangeSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startChangeSetAsync(array $args = [])
 */
class MarketplaceCatalogClient extends AwsClient {}
