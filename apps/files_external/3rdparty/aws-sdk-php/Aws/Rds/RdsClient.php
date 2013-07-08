<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\Rds;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Enum\ClientOptions as Options;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with Amazon Relational Database Service
 *
 * @method Model addSourceIdentifierToSubscription(array $args = array()) {@command Rds AddSourceIdentifierToSubscription}
 * @method Model addTagsToResource(array $args = array()) {@command Rds AddTagsToResource}
 * @method Model authorizeDBSecurityGroupIngress(array $args = array()) {@command Rds AuthorizeDBSecurityGroupIngress}
 * @method Model copyDBSnapshot(array $args = array()) {@command Rds CopyDBSnapshot}
 * @method Model createDBInstance(array $args = array()) {@command Rds CreateDBInstance}
 * @method Model createDBInstanceReadReplica(array $args = array()) {@command Rds CreateDBInstanceReadReplica}
 * @method Model createDBParameterGroup(array $args = array()) {@command Rds CreateDBParameterGroup}
 * @method Model createDBSecurityGroup(array $args = array()) {@command Rds CreateDBSecurityGroup}
 * @method Model createDBSnapshot(array $args = array()) {@command Rds CreateDBSnapshot}
 * @method Model createDBSubnetGroup(array $args = array()) {@command Rds CreateDBSubnetGroup}
 * @method Model createEventSubscription(array $args = array()) {@command Rds CreateEventSubscription}
 * @method Model createOptionGroup(array $args = array()) {@command Rds CreateOptionGroup}
 * @method Model deleteDBInstance(array $args = array()) {@command Rds DeleteDBInstance}
 * @method Model deleteDBParameterGroup(array $args = array()) {@command Rds DeleteDBParameterGroup}
 * @method Model deleteDBSecurityGroup(array $args = array()) {@command Rds DeleteDBSecurityGroup}
 * @method Model deleteDBSnapshot(array $args = array()) {@command Rds DeleteDBSnapshot}
 * @method Model deleteDBSubnetGroup(array $args = array()) {@command Rds DeleteDBSubnetGroup}
 * @method Model deleteEventSubscription(array $args = array()) {@command Rds DeleteEventSubscription}
 * @method Model deleteOptionGroup(array $args = array()) {@command Rds DeleteOptionGroup}
 * @method Model describeDBEngineVersions(array $args = array()) {@command Rds DescribeDBEngineVersions}
 * @method Model describeDBInstances(array $args = array()) {@command Rds DescribeDBInstances}
 * @method Model describeDBLogFiles(array $args = array()) {@command Rds DescribeDBLogFiles}
 * @method Model describeDBParameterGroups(array $args = array()) {@command Rds DescribeDBParameterGroups}
 * @method Model describeDBParameters(array $args = array()) {@command Rds DescribeDBParameters}
 * @method Model describeDBSecurityGroups(array $args = array()) {@command Rds DescribeDBSecurityGroups}
 * @method Model describeDBSnapshots(array $args = array()) {@command Rds DescribeDBSnapshots}
 * @method Model describeDBSubnetGroups(array $args = array()) {@command Rds DescribeDBSubnetGroups}
 * @method Model describeEngineDefaultParameters(array $args = array()) {@command Rds DescribeEngineDefaultParameters}
 * @method Model describeEventCategories(array $args = array()) {@command Rds DescribeEventCategories}
 * @method Model describeEventSubscriptions(array $args = array()) {@command Rds DescribeEventSubscriptions}
 * @method Model describeEvents(array $args = array()) {@command Rds DescribeEvents}
 * @method Model describeOptionGroupOptions(array $args = array()) {@command Rds DescribeOptionGroupOptions}
 * @method Model describeOptionGroups(array $args = array()) {@command Rds DescribeOptionGroups}
 * @method Model describeOrderableDBInstanceOptions(array $args = array()) {@command Rds DescribeOrderableDBInstanceOptions}
 * @method Model describeReservedDBInstances(array $args = array()) {@command Rds DescribeReservedDBInstances}
 * @method Model describeReservedDBInstancesOfferings(array $args = array()) {@command Rds DescribeReservedDBInstancesOfferings}
 * @method Model downloadDBLogFilePortion(array $args = array()) {@command Rds DownloadDBLogFilePortion}
 * @method Model listTagsForResource(array $args = array()) {@command Rds ListTagsForResource}
 * @method Model modifyDBInstance(array $args = array()) {@command Rds ModifyDBInstance}
 * @method Model modifyDBParameterGroup(array $args = array()) {@command Rds ModifyDBParameterGroup}
 * @method Model modifyDBSubnetGroup(array $args = array()) {@command Rds ModifyDBSubnetGroup}
 * @method Model modifyEventSubscription(array $args = array()) {@command Rds ModifyEventSubscription}
 * @method Model modifyOptionGroup(array $args = array()) {@command Rds ModifyOptionGroup}
 * @method Model promoteReadReplica(array $args = array()) {@command Rds PromoteReadReplica}
 * @method Model purchaseReservedDBInstancesOffering(array $args = array()) {@command Rds PurchaseReservedDBInstancesOffering}
 * @method Model rebootDBInstance(array $args = array()) {@command Rds RebootDBInstance}
 * @method Model removeSourceIdentifierFromSubscription(array $args = array()) {@command Rds RemoveSourceIdentifierFromSubscription}
 * @method Model removeTagsFromResource(array $args = array()) {@command Rds RemoveTagsFromResource}
 * @method Model resetDBParameterGroup(array $args = array()) {@command Rds ResetDBParameterGroup}
 * @method Model restoreDBInstanceFromDBSnapshot(array $args = array()) {@command Rds RestoreDBInstanceFromDBSnapshot}
 * @method Model restoreDBInstanceToPointInTime(array $args = array()) {@command Rds RestoreDBInstanceToPointInTime}
 * @method Model revokeDBSecurityGroupIngress(array $args = array()) {@command Rds RevokeDBSecurityGroupIngress}
 * @method waitUntilDBInstanceAvailable(array $input) Wait using the DBInstanceAvailable waiter. The input array uses the parameters of the DescribeDBInstances operation and waiter specific settings
 * @method waitUntilDBInstanceDeleted(array $input) Wait using the DBInstanceDeleted waiter. The input array uses the parameters of the DescribeDBInstances operation and waiter specific settings
 * @method ResourceIteratorInterface getDescribeDBEngineVersionsIterator(array $args = array()) The input array uses the parameters of the DescribeDBEngineVersions operation
 * @method ResourceIteratorInterface getDescribeDBInstancesIterator(array $args = array()) The input array uses the parameters of the DescribeDBInstances operation
 * @method ResourceIteratorInterface getDescribeDBLogFilesIterator(array $args = array()) The input array uses the parameters of the DescribeDBLogFiles operation
 * @method ResourceIteratorInterface getDescribeDBParameterGroupsIterator(array $args = array()) The input array uses the parameters of the DescribeDBParameterGroups operation
 * @method ResourceIteratorInterface getDescribeDBParametersIterator(array $args = array()) The input array uses the parameters of the DescribeDBParameters operation
 * @method ResourceIteratorInterface getDescribeDBSecurityGroupsIterator(array $args = array()) The input array uses the parameters of the DescribeDBSecurityGroups operation
 * @method ResourceIteratorInterface getDescribeDBSnapshotsIterator(array $args = array()) The input array uses the parameters of the DescribeDBSnapshots operation
 * @method ResourceIteratorInterface getDescribeDBSubnetGroupsIterator(array $args = array()) The input array uses the parameters of the DescribeDBSubnetGroups operation
 * @method ResourceIteratorInterface getDescribeEngineDefaultParametersIterator(array $args = array()) The input array uses the parameters of the DescribeEngineDefaultParameters operation
 * @method ResourceIteratorInterface getDescribeEventSubscriptionsIterator(array $args = array()) The input array uses the parameters of the DescribeEventSubscriptions operation
 * @method ResourceIteratorInterface getDescribeEventsIterator(array $args = array()) The input array uses the parameters of the DescribeEvents operation
 * @method ResourceIteratorInterface getDescribeOptionGroupOptionsIterator(array $args = array()) The input array uses the parameters of the DescribeOptionGroupOptions operation
 * @method ResourceIteratorInterface getDescribeOptionGroupsIterator(array $args = array()) The input array uses the parameters of the DescribeOptionGroups operation
 * @method ResourceIteratorInterface getDescribeOrderableDBInstanceOptionsIterator(array $args = array()) The input array uses the parameters of the DescribeOrderableDBInstanceOptions operation
 * @method ResourceIteratorInterface getDescribeReservedDBInstancesIterator(array $args = array()) The input array uses the parameters of the DescribeReservedDBInstances operation
 * @method ResourceIteratorInterface getDescribeReservedDBInstancesOfferingsIterator(array $args = array()) The input array uses the parameters of the DescribeReservedDBInstancesOfferings operation
 * @method ResourceIteratorInterface getDownloadDBLogFilePortionIterator(array $args = array()) The input array uses the parameters of the DownloadDBLogFilePortion operation
 * @method ResourceIteratorInterface getListTagsForResourceIterator(array $args = array()) The input array uses the parameters of the ListTagsForResource operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-rds.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.Rds.RdsClient.html API docs
 */
class RdsClient extends AbstractClient
{
    const LATEST_API_VERSION = '2013-05-15';

    /**
     * Factory method to create a new Amazon Relational Database Service client using an array of configuration options.
     *
     * The following array keys and values are available options:
     *
     * - Credential options (`key`, `secret`, and optional `token` OR `credentials` is required)
     *     - key: AWS Access Key ID
     *     - secret: AWS secret access key
     *     - credentials: You can optionally provide a custom `Aws\Common\Credentials\CredentialsInterface` object
     *     - token: Custom AWS security token to use with request authentication
     *     - token.ttd: UNIX timestamp for when the custom credentials expire
     *     - credentials.cache.key: Optional custom cache key to use with the credentials
     * - Region and Endpoint options (a `region` and optional `scheme` OR a `base_url` is required)
     *     - region: Region name (e.g. 'us-east-1', 'us-west-1', 'us-west-2', 'eu-west-1', etc...)
     *     - scheme: URI Scheme of the base URL (e.g. 'https', 'http').
     *     - base_url: Instead of using a `region` and `scheme`, you can specify a custom base URL for the client
     * - Generic client options
     *     - ssl.cert: Set to true to use the bundled CA cert or pass the full path to an SSL certificate bundle. This
     *           option should be used when you encounter curl error code 60.
     *     - curl.CURLOPT_VERBOSE: Set to true to output curl debug information during transfers
     *     - curl.*: Prefix any available cURL option with `curl.` to add cURL options to each request.
     *           See: http://www.php.net/manual/en/function.curl-setopt.php
     *     - service.description.cache.ttl: Optional TTL used for the service description cache
     * - Signature options
     *     - signature: You can optionally provide a custom signature implementation used to sign requests
     *     - signature.service: Set to explicitly override the service name used in signatures
     *     - signature.region:  Set to explicitly override the region name used in signatures
     * - Exponential backoff options
     *     - client.backoff.logger: `Guzzle\Common\Log\LogAdapterInterface` object used to log backoff retries. Use
     *           'debug' to emit PHP warnings when a retry is issued.
     *     - client.backoff.logger.template: Optional template to use for exponential backoff log messages. See
     *           `Guzzle\Http\Plugin\ExponentialBackoffLogger` for formatting information.
     *
     * @param array|Collection $config Client configuration data
     *
     * @return self
     */
    public static function factory($config = array())
    {
        return ClientBuilder::factory(__NAMESPACE__)
            ->setConfig($config)
            ->setConfigDefaults(array(
                Options::VERSION             => self::LATEST_API_VERSION,
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/rds-%s.php'
            ))
            ->build();
    }
}
