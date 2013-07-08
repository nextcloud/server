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

namespace Aws\Sns;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Enum\ClientOptions as Options;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with Amazon Simple Notification Service
 *
 * @method Model addPermission(array $args = array()) {@command Sns AddPermission}
 * @method Model confirmSubscription(array $args = array()) {@command Sns ConfirmSubscription}
 * @method Model createTopic(array $args = array()) {@command Sns CreateTopic}
 * @method Model deleteTopic(array $args = array()) {@command Sns DeleteTopic}
 * @method Model getSubscriptionAttributes(array $args = array()) {@command Sns GetSubscriptionAttributes}
 * @method Model getTopicAttributes(array $args = array()) {@command Sns GetTopicAttributes}
 * @method Model listSubscriptions(array $args = array()) {@command Sns ListSubscriptions}
 * @method Model listSubscriptionsByTopic(array $args = array()) {@command Sns ListSubscriptionsByTopic}
 * @method Model listTopics(array $args = array()) {@command Sns ListTopics}
 * @method Model publish(array $args = array()) {@command Sns Publish}
 * @method Model removePermission(array $args = array()) {@command Sns RemovePermission}
 * @method Model setSubscriptionAttributes(array $args = array()) {@command Sns SetSubscriptionAttributes}
 * @method Model setTopicAttributes(array $args = array()) {@command Sns SetTopicAttributes}
 * @method Model subscribe(array $args = array()) {@command Sns Subscribe}
 * @method Model unsubscribe(array $args = array()) {@command Sns Unsubscribe}
 * @method ResourceIteratorInterface getListSubscriptionsIterator(array $args = array()) The input array uses the parameters of the ListSubscriptions operation
 * @method ResourceIteratorInterface getListSubscriptionsByTopicIterator(array $args = array()) The input array uses the parameters of the ListSubscriptionsByTopic operation
 * @method ResourceIteratorInterface getListTopicsIterator(array $args = array()) The input array uses the parameters of the ListTopics operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-sns.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.Sns.SnsClient.html API docs
 */
class SnsClient extends AbstractClient
{
    const LATEST_API_VERSION = '2010-03-31';

    /**
     * Factory method to create a new Amazon Simple Notification Service client using an array of configuration options.
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
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/sns-%s.php'
            ))
            ->build();
    }
}
