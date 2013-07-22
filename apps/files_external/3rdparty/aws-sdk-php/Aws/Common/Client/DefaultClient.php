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

namespace Aws\Common\Client;

use Aws\Common\Enum\ClientOptions as Options;
use Guzzle\Common\Collection;

/**
 * Generic client for interacting with an AWS service
 */
class DefaultClient extends AbstractClient
{
    /**
     * Factory method to create a default client using an array of configuration options.
     *
     * The following array keys and values are available options:
     *
     * - Credential options (`key`, `secret`, and optional `token` OR `credentials` is required)
     *     - key: AWS Access Key ID
     *     - secret: AWS secret access key
     *     - credentials: You can optionally provide a custom `Aws\Common\Credentials\CredentialsInterface` object
     *     - token: Custom AWS security token to use with request authentication
     *     - token.ttd: UNIX timestamp for when the custom credentials expire
     *     - credentials.cache: Used to cache credentials when using providers that require HTTP requests. Set the true
     *           to use the default APC cache or provide a `Guzzle\Cache\CacheAdapterInterface` object.
     *     - credentials.cache.key: Optional custom cache key to use with the credentials
     *     - credentials.client: Pass this option to specify a custom `Guzzle\Http\ClientInterface` to use if your
     *           credentials require a HTTP request (e.g. RefreshableInstanceProfileCredentials)
     * - Region and Endpoint options (a `region` and optional `scheme` OR a `base_url` is required)
     *     - region: Region name (e.g. 'us-east-1', 'us-west-1', 'us-west-2', 'eu-west-1', etc...)
     *     - scheme: URI Scheme of the base URL (e.g. 'https', 'http').
     *     - service: Specify the name of the service
     *     - base_url: Instead of using a `region` and `scheme`, you can specify a custom base URL for the client
     * - Signature options
     *     - signature: You can optionally provide a custom signature implementation used to sign requests
     *     - signature.service: Set to explicitly override the service name used in signatures
     *     - signature.region:  Set to explicitly override the region name used in signatures
     * - Exponential backoff options
     *     - client.backoff.logger: `Guzzle\Log\LogAdapterInterface` object used to log backoff retries. Use
     *           'debug' to emit PHP warnings when a retry is issued.
     *     - client.backoff.logger.template: Optional template to use for exponential backoff log messages. See
     *           `Guzzle\Plugin\Backoff\BackoffLogger` for formatting information.
     * - Generic client options
     *     - ssl.certificate_authority: Set to true to use the bundled CA cert (default), system to use the certificate
     *       bundled with your system, or pass the full path to an SSL certificate bundle. This option should be used
     *       when you encounter curl error code 60.
     *     - curl.CURLOPT_VERBOSE: Set to true to output curl debug information during transfers
     *     - curl.*: Prefix any available cURL option with `curl.` to add cURL options to each request.
     *           See: http://www.php.net/manual/en/function.curl-setopt.php
     *
     * @param array|Collection $config Client configuration data
     *
     * @return self
     */
    public static function factory($config = array())
    {
        return ClientBuilder::factory()
            ->setConfig($config)
            ->setConfigDefaults(array(Options::SCHEME => 'https'))
            ->build();
    }
}
