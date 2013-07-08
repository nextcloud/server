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

namespace Aws\OpsWorks;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\Parser\JsonQueryExceptionParser;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with AWS OpsWorks
 *
 * @method Model attachElasticLoadBalancer(array $args = array()) {@command OpsWorks AttachElasticLoadBalancer}
 * @method Model cloneStack(array $args = array()) {@command OpsWorks CloneStack}
 * @method Model createApp(array $args = array()) {@command OpsWorks CreateApp}
 * @method Model createDeployment(array $args = array()) {@command OpsWorks CreateDeployment}
 * @method Model createInstance(array $args = array()) {@command OpsWorks CreateInstance}
 * @method Model createLayer(array $args = array()) {@command OpsWorks CreateLayer}
 * @method Model createStack(array $args = array()) {@command OpsWorks CreateStack}
 * @method Model createUserProfile(array $args = array()) {@command OpsWorks CreateUserProfile}
 * @method Model deleteApp(array $args = array()) {@command OpsWorks DeleteApp}
 * @method Model deleteInstance(array $args = array()) {@command OpsWorks DeleteInstance}
 * @method Model deleteLayer(array $args = array()) {@command OpsWorks DeleteLayer}
 * @method Model deleteStack(array $args = array()) {@command OpsWorks DeleteStack}
 * @method Model deleteUserProfile(array $args = array()) {@command OpsWorks DeleteUserProfile}
 * @method Model describeApps(array $args = array()) {@command OpsWorks DescribeApps}
 * @method Model describeCommands(array $args = array()) {@command OpsWorks DescribeCommands}
 * @method Model describeDeployments(array $args = array()) {@command OpsWorks DescribeDeployments}
 * @method Model describeElasticIps(array $args = array()) {@command OpsWorks DescribeElasticIps}
 * @method Model describeElasticLoadBalancers(array $args = array()) {@command OpsWorks DescribeElasticLoadBalancers}
 * @method Model describeInstances(array $args = array()) {@command OpsWorks DescribeInstances}
 * @method Model describeLayers(array $args = array()) {@command OpsWorks DescribeLayers}
 * @method Model describeLoadBasedAutoScaling(array $args = array()) {@command OpsWorks DescribeLoadBasedAutoScaling}
 * @method Model describePermissions(array $args = array()) {@command OpsWorks DescribePermissions}
 * @method Model describeRaidArrays(array $args = array()) {@command OpsWorks DescribeRaidArrays}
 * @method Model describeServiceErrors(array $args = array()) {@command OpsWorks DescribeServiceErrors}
 * @method Model describeStacks(array $args = array()) {@command OpsWorks DescribeStacks}
 * @method Model describeTimeBasedAutoScaling(array $args = array()) {@command OpsWorks DescribeTimeBasedAutoScaling}
 * @method Model describeUserProfiles(array $args = array()) {@command OpsWorks DescribeUserProfiles}
 * @method Model describeVolumes(array $args = array()) {@command OpsWorks DescribeVolumes}
 * @method Model detachElasticLoadBalancer(array $args = array()) {@command OpsWorks DetachElasticLoadBalancer}
 * @method Model getHostnameSuggestion(array $args = array()) {@command OpsWorks GetHostnameSuggestion}
 * @method Model rebootInstance(array $args = array()) {@command OpsWorks RebootInstance}
 * @method Model setLoadBasedAutoScaling(array $args = array()) {@command OpsWorks SetLoadBasedAutoScaling}
 * @method Model setPermission(array $args = array()) {@command OpsWorks SetPermission}
 * @method Model setTimeBasedAutoScaling(array $args = array()) {@command OpsWorks SetTimeBasedAutoScaling}
 * @method Model startInstance(array $args = array()) {@command OpsWorks StartInstance}
 * @method Model startStack(array $args = array()) {@command OpsWorks StartStack}
 * @method Model stopInstance(array $args = array()) {@command OpsWorks StopInstance}
 * @method Model stopStack(array $args = array()) {@command OpsWorks StopStack}
 * @method Model updateApp(array $args = array()) {@command OpsWorks UpdateApp}
 * @method Model updateInstance(array $args = array()) {@command OpsWorks UpdateInstance}
 * @method Model updateLayer(array $args = array()) {@command OpsWorks UpdateLayer}
 * @method Model updateStack(array $args = array()) {@command OpsWorks UpdateStack}
 * @method Model updateUserProfile(array $args = array()) {@command OpsWorks UpdateUserProfile}
 * @method ResourceIteratorInterface getDescribeAppsIterator(array $args = array()) The input array uses the parameters of the DescribeApps operation
 * @method ResourceIteratorInterface getDescribeCommandsIterator(array $args = array()) The input array uses the parameters of the DescribeCommands operation
 * @method ResourceIteratorInterface getDescribeDeploymentsIterator(array $args = array()) The input array uses the parameters of the DescribeDeployments operation
 * @method ResourceIteratorInterface getDescribeElasticIpsIterator(array $args = array()) The input array uses the parameters of the DescribeElasticIps operation
 * @method ResourceIteratorInterface getDescribeElasticLoadBalancersIterator(array $args = array()) The input array uses the parameters of the DescribeElasticLoadBalancers operation
 * @method ResourceIteratorInterface getDescribeInstancesIterator(array $args = array()) The input array uses the parameters of the DescribeInstances operation
 * @method ResourceIteratorInterface getDescribeLayersIterator(array $args = array()) The input array uses the parameters of the DescribeLayers operation
 * @method ResourceIteratorInterface getDescribeLoadBasedAutoScalingIterator(array $args = array()) The input array uses the parameters of the DescribeLoadBasedAutoScaling operation
 * @method ResourceIteratorInterface getDescribeRaidArraysIterator(array $args = array()) The input array uses the parameters of the DescribeRaidArrays operation
 * @method ResourceIteratorInterface getDescribeServiceErrorsIterator(array $args = array()) The input array uses the parameters of the DescribeServiceErrors operation
 * @method ResourceIteratorInterface getDescribeStacksIterator(array $args = array()) The input array uses the parameters of the DescribeStacks operation
 * @method ResourceIteratorInterface getDescribeTimeBasedAutoScalingIterator(array $args = array()) The input array uses the parameters of the DescribeTimeBasedAutoScaling operation
 * @method ResourceIteratorInterface getDescribeUserProfilesIterator(array $args = array()) The input array uses the parameters of the DescribeUserProfiles operation
 * @method ResourceIteratorInterface getDescribeVolumesIterator(array $args = array()) The input array uses the parameters of the DescribeVolumes operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-opsworks.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.OpsWorks.OpsWorksClient.html API docs
 */
class OpsWorksClient extends AbstractClient
{
    const LATEST_API_VERSION = '2013-02-18';

    /**
     * Factory method to create a new AWS OpsWorks client using an array of configuration options.
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
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/opsworks-%s.php'
            ))
            ->setExceptionParser(new JsonQueryExceptionParser())
            ->build();
    }
}
