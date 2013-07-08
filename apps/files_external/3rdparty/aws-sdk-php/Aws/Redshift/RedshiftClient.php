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

namespace Aws\Redshift;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Enum\ClientOptions as Options;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with Amazon Redshift
 *
 * @method Model authorizeClusterSecurityGroupIngress(array $args = array()) {@command Redshift AuthorizeClusterSecurityGroupIngress}
 * @method Model copyClusterSnapshot(array $args = array()) {@command Redshift CopyClusterSnapshot}
 * @method Model createCluster(array $args = array()) {@command Redshift CreateCluster}
 * @method Model createClusterParameterGroup(array $args = array()) {@command Redshift CreateClusterParameterGroup}
 * @method Model createClusterSecurityGroup(array $args = array()) {@command Redshift CreateClusterSecurityGroup}
 * @method Model createClusterSnapshot(array $args = array()) {@command Redshift CreateClusterSnapshot}
 * @method Model createClusterSubnetGroup(array $args = array()) {@command Redshift CreateClusterSubnetGroup}
 * @method Model deleteCluster(array $args = array()) {@command Redshift DeleteCluster}
 * @method Model deleteClusterParameterGroup(array $args = array()) {@command Redshift DeleteClusterParameterGroup}
 * @method Model deleteClusterSecurityGroup(array $args = array()) {@command Redshift DeleteClusterSecurityGroup}
 * @method Model deleteClusterSnapshot(array $args = array()) {@command Redshift DeleteClusterSnapshot}
 * @method Model deleteClusterSubnetGroup(array $args = array()) {@command Redshift DeleteClusterSubnetGroup}
 * @method Model describeClusterParameterGroups(array $args = array()) {@command Redshift DescribeClusterParameterGroups}
 * @method Model describeClusterParameters(array $args = array()) {@command Redshift DescribeClusterParameters}
 * @method Model describeClusterSecurityGroups(array $args = array()) {@command Redshift DescribeClusterSecurityGroups}
 * @method Model describeClusterSnapshots(array $args = array()) {@command Redshift DescribeClusterSnapshots}
 * @method Model describeClusterSubnetGroups(array $args = array()) {@command Redshift DescribeClusterSubnetGroups}
 * @method Model describeClusterVersions(array $args = array()) {@command Redshift DescribeClusterVersions}
 * @method Model describeClusters(array $args = array()) {@command Redshift DescribeClusters}
 * @method Model describeDefaultClusterParameters(array $args = array()) {@command Redshift DescribeDefaultClusterParameters}
 * @method Model describeEvents(array $args = array()) {@command Redshift DescribeEvents}
 * @method Model describeOrderableClusterOptions(array $args = array()) {@command Redshift DescribeOrderableClusterOptions}
 * @method Model describeReservedNodeOfferings(array $args = array()) {@command Redshift DescribeReservedNodeOfferings}
 * @method Model describeReservedNodes(array $args = array()) {@command Redshift DescribeReservedNodes}
 * @method Model describeResize(array $args = array()) {@command Redshift DescribeResize}
 * @method Model modifyCluster(array $args = array()) {@command Redshift ModifyCluster}
 * @method Model modifyClusterParameterGroup(array $args = array()) {@command Redshift ModifyClusterParameterGroup}
 * @method Model modifyClusterSubnetGroup(array $args = array()) {@command Redshift ModifyClusterSubnetGroup}
 * @method Model purchaseReservedNodeOffering(array $args = array()) {@command Redshift PurchaseReservedNodeOffering}
 * @method Model rebootCluster(array $args = array()) {@command Redshift RebootCluster}
 * @method Model resetClusterParameterGroup(array $args = array()) {@command Redshift ResetClusterParameterGroup}
 * @method Model restoreFromClusterSnapshot(array $args = array()) {@command Redshift RestoreFromClusterSnapshot}
 * @method Model revokeClusterSecurityGroupIngress(array $args = array()) {@command Redshift RevokeClusterSecurityGroupIngress}
 * @method waitUntilClusterAvailable(array $input) Wait using the ClusterAvailable waiter. The input array uses the parameters of the DescribeClusters operation and waiter specific settings
 * @method waitUntilClusterDeleted(array $input) Wait using the ClusterDeleted waiter. The input array uses the parameters of the DescribeClusters operation and waiter specific settings
 * @method waitUntilSnapshotAvailable(array $input) Wait using the SnapshotAvailable waiter. The input array uses the parameters of the DescribeClusterSnapshots operation and waiter specific settings
 * @method ResourceIteratorInterface getDescribeClusterParameterGroupsIterator(array $args = array()) The input array uses the parameters of the DescribeClusterParameterGroups operation
 * @method ResourceIteratorInterface getDescribeClusterParametersIterator(array $args = array()) The input array uses the parameters of the DescribeClusterParameters operation
 * @method ResourceIteratorInterface getDescribeClusterSecurityGroupsIterator(array $args = array()) The input array uses the parameters of the DescribeClusterSecurityGroups operation
 * @method ResourceIteratorInterface getDescribeClusterSnapshotsIterator(array $args = array()) The input array uses the parameters of the DescribeClusterSnapshots operation
 * @method ResourceIteratorInterface getDescribeClusterSubnetGroupsIterator(array $args = array()) The input array uses the parameters of the DescribeClusterSubnetGroups operation
 * @method ResourceIteratorInterface getDescribeClusterVersionsIterator(array $args = array()) The input array uses the parameters of the DescribeClusterVersions operation
 * @method ResourceIteratorInterface getDescribeClustersIterator(array $args = array()) The input array uses the parameters of the DescribeClusters operation
 * @method ResourceIteratorInterface getDescribeDefaultClusterParametersIterator(array $args = array()) The input array uses the parameters of the DescribeDefaultClusterParameters operation
 * @method ResourceIteratorInterface getDescribeEventsIterator(array $args = array()) The input array uses the parameters of the DescribeEvents operation
 * @method ResourceIteratorInterface getDescribeOrderableClusterOptionsIterator(array $args = array()) The input array uses the parameters of the DescribeOrderableClusterOptions operation
 * @method ResourceIteratorInterface getDescribeReservedNodeOfferingsIterator(array $args = array()) The input array uses the parameters of the DescribeReservedNodeOfferings operation
 * @method ResourceIteratorInterface getDescribeReservedNodesIterator(array $args = array()) The input array uses the parameters of the DescribeReservedNodes operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-redshift.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.Redshift.RedshiftClient.html API docs
 */
class RedshiftClient extends AbstractClient
{
    const LATEST_API_VERSION = '2012-12-01';

    /**
     * Factory method to create a new Amazon Redshift client using an array of configuration options.
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
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/redshift-%s.php'
            ))
            ->build();
    }
}
