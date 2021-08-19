<?php
namespace Aws\Health;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Health APIs and Notifications** service.
 * @method \Aws\Result describeAffectedAccountsForOrganization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAffectedAccountsForOrganizationAsync(array $args = [])
 * @method \Aws\Result describeAffectedEntities(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAffectedEntitiesAsync(array $args = [])
 * @method \Aws\Result describeAffectedEntitiesForOrganization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAffectedEntitiesForOrganizationAsync(array $args = [])
 * @method \Aws\Result describeEntityAggregates(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEntityAggregatesAsync(array $args = [])
 * @method \Aws\Result describeEventAggregates(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEventAggregatesAsync(array $args = [])
 * @method \Aws\Result describeEventDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEventDetailsAsync(array $args = [])
 * @method \Aws\Result describeEventDetailsForOrganization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEventDetailsForOrganizationAsync(array $args = [])
 * @method \Aws\Result describeEventTypes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEventTypesAsync(array $args = [])
 * @method \Aws\Result describeEvents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEventsAsync(array $args = [])
 * @method \Aws\Result describeEventsForOrganization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEventsForOrganizationAsync(array $args = [])
 * @method \Aws\Result describeHealthServiceStatusForOrganization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeHealthServiceStatusForOrganizationAsync(array $args = [])
 * @method \Aws\Result disableHealthServiceAccessForOrganization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disableHealthServiceAccessForOrganizationAsync(array $args = [])
 * @method \Aws\Result enableHealthServiceAccessForOrganization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise enableHealthServiceAccessForOrganizationAsync(array $args = [])
 */
class HealthClient extends AwsClient {}
