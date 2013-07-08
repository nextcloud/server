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

namespace Aws\Ses;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Enum\ClientOptions as Options;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with Amazon Simple Email Service
 *
 * @method Model deleteIdentity(array $args = array()) {@command Ses DeleteIdentity}
 * @method Model deleteVerifiedEmailAddress(array $args = array()) {@command Ses DeleteVerifiedEmailAddress}
 * @method Model getIdentityDkimAttributes(array $args = array()) {@command Ses GetIdentityDkimAttributes}
 * @method Model getIdentityNotificationAttributes(array $args = array()) {@command Ses GetIdentityNotificationAttributes}
 * @method Model getIdentityVerificationAttributes(array $args = array()) {@command Ses GetIdentityVerificationAttributes}
 * @method Model getSendQuota(array $args = array()) {@command Ses GetSendQuota}
 * @method Model getSendStatistics(array $args = array()) {@command Ses GetSendStatistics}
 * @method Model listIdentities(array $args = array()) {@command Ses ListIdentities}
 * @method Model listVerifiedEmailAddresses(array $args = array()) {@command Ses ListVerifiedEmailAddresses}
 * @method Model sendEmail(array $args = array()) {@command Ses SendEmail}
 * @method Model sendRawEmail(array $args = array()) {@command Ses SendRawEmail}
 * @method Model setIdentityDkimEnabled(array $args = array()) {@command Ses SetIdentityDkimEnabled}
 * @method Model setIdentityFeedbackForwardingEnabled(array $args = array()) {@command Ses SetIdentityFeedbackForwardingEnabled}
 * @method Model setIdentityNotificationTopic(array $args = array()) {@command Ses SetIdentityNotificationTopic}
 * @method Model verifyDomainDkim(array $args = array()) {@command Ses VerifyDomainDkim}
 * @method Model verifyDomainIdentity(array $args = array()) {@command Ses VerifyDomainIdentity}
 * @method Model verifyEmailAddress(array $args = array()) {@command Ses VerifyEmailAddress}
 * @method Model verifyEmailIdentity(array $args = array()) {@command Ses VerifyEmailIdentity}
 * @method waitUntilIdentityExists(array $input) Wait using the IdentityExists waiter. The input array uses the parameters of the GetIdentityVerificationAttributes operation and waiter specific settings
 * @method ResourceIteratorInterface getListIdentitiesIterator(array $args = array()) The input array uses the parameters of the ListIdentities operation
 * @method ResourceIteratorInterface getListVerifiedEmailAddressesIterator(array $args = array()) The input array uses the parameters of the ListVerifiedEmailAddresses operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-ses.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.Ses.SesClient.html API docs
 */
class SesClient extends AbstractClient
{
    const LATEST_API_VERSION = '2010-12-01';

    /**
     * Factory method to create a new Amazon Simple Email Service client using an array of configuration options.
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
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/ses-%s.php',
                Options::SIGNATURE_SERVICE   => 'ses',
            ))
            ->build();
    }
}
