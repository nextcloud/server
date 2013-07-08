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

namespace Aws\Sts;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Credentials\Credentials;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;

/**
 * Client to interact with AWS Security Token Service
 *
 * @method Model assumeRole(array $args = array()) {@command Sts AssumeRole}
 * @method Model assumeRoleWithWebIdentity(array $args = array()) {@command Sts AssumeRoleWithWebIdentity}
 * @method Model getFederationToken(array $args = array()) {@command Sts GetFederationToken}
 * @method Model getSessionToken(array $args = array()) {@command Sts GetSessionToken}
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-sts.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.Sts.StsClient.html API docs
 */
class StsClient extends AbstractClient
{
    const LATEST_API_VERSION = '2011-06-15';

    /**
     * Factory method to create a new Amazon STS client using an array of configuration options:
     *
     * Credential options (`key`, `secret`, and optional `token` OR `credentials` is required)
     *
     * - key: AWS Access Key ID
     * - secret: AWS secret access key
     * - credentials: You can optionally provide a custom `Aws\Common\Credentials\CredentialsInterface` object
     * - token: Custom AWS security token to use with request authentication
     * - token.ttd: UNIX timestamp for when the custom credentials expire
     * - credentials.cache: Used to cache credentials when using providers that require HTTP requests. Set the true
     *   to use the default APC cache or provide a `Guzzle\Cache\CacheAdapterInterface` object.
     * - credentials.cache.key: Optional custom cache key to use with the credentials
     * - credentials.client: Pass this option to specify a custom `Guzzle\Http\ClientInterface` to use if your
     *   credentials require a HTTP request (e.g. RefreshableInstanceProfileCredentials)
     *
     * Region and Endpoint options (a `region` and optional `scheme` OR a `base_url` is required)
     *
     * - region: Region name (e.g. 'us-east-1', 'us-west-1', 'us-west-2', 'eu-west-1', etc...)
     * - scheme: URI Scheme of the base URL (e.g. 'https', 'http').
     * - base_url: Instead of using a `region` and `scheme`, you can specify a custom base URL for the client
     *
     * Generic client options
     *
     * - ssl.certificate_authority: Set to true to use the bundled CA cert (default), system to use the certificate
     *   bundled with your system, or pass the full path to an SSL certificate bundle. This option should be used when
     *   you encounter curl error code 60.
     * - curl.options: Array of cURL options to apply to every request.
     *   See http://www.php.net/manual/en/function.curl-setopt.php for a list of available options
     * - signature: You can optionally provide a custom signature implementation used to sign requests
     * - signature.service: Set to explicitly override the service name used in signatures
     * - signature.region:  Set to explicitly override the region name used in signatures
     * - client.backoff.logger: `Guzzle\Log\LogAdapterInterface` object used to log backoff retries. Use
     *   'debug' to emit PHP warnings when a retry is issued.
     * - client.backoff.logger.template: Optional template to use for exponential backoff log messages. See
     *   `Guzzle\Plugin\Backoff\BackoffLogger` for formatting information.
     *
     * @param array|Collection $config Client configuration data
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public static function factory($config = array())
    {
        // Always need long term credentials
        if (!isset($config[Options::CREDENTIALS]) && isset($config[Options::TOKEN])) {
            throw new InvalidArgumentException('You must use long-term credentials with Amazon STS');
        }

        // Construct the STS client with the client builder
        return ClientBuilder::factory(__NAMESPACE__)
            ->setConfig($config)
            ->setConfigDefaults(array(
                Options::VERSION             => self::LATEST_API_VERSION,
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/sts-%s.php'
            ))
            ->build();
    }

    /**
     * Creates a credentials object from the credential data return by an STS operation
     *
     * @param Model $result The result of an STS operation
     *
     * @return Credentials
     * @throws InvalidArgumentException if the result does not contain credential data
     */
    public function createCredentials(Model $result)
    {
        if (!$result->hasKey('Credentials')) {
            throw new InvalidArgumentException('The modeled result provided contained no credentials.');
        }

        return new Credentials(
            $result->getPath('Credentials/AccessKeyId'),
            $result->getPath('Credentials/SecretAccessKey'),
            $result->getPath('Credentials/SessionToken'),
            $result->getPath('Credentials/Expiration')
        );
    }
}
