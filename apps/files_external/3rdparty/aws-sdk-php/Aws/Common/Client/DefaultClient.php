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
     * Credential options ((`key`, `secret`, and optional `token`) OR `credentials` is required):
     *
     * - key: AWS Access Key ID
     * - secret: AWS secret access key
     * - credentials: You can optionally provide a custom `Aws\Common\Credentials\CredentialsInterface` object
     * - token: Custom AWS security token to use with request authentication. Please note that not all services accept temporary credentials. See http://docs.aws.amazon.com/STS/latest/UsingSTS/UsingTokens.html
     * - token.ttd: UNIX timestamp for when the custom credentials expire
     * - credentials.cache.key: Optional custom cache key to use with the credentials
     * - credentials.client: Pass this option to specify a custom `Guzzle\Http\ClientInterface` to use if your credentials require a HTTP request (e.g. RefreshableInstanceProfileCredentials)
     *
     * Region and endpoint options (Some services do not require a region while others do. Check the service specific user guide documentation for details):
     *
     * - region: Region name (e.g. 'us-east-1', 'us-west-1', 'us-west-2', 'eu-west-1', etc...)
     * - scheme: URI Scheme of the base URL (e.g. 'https', 'http') used when base_url is not supplied
     * - base_url: Allows you to specify a custom endpoint instead of building one from the region and scheme
     *
     * Generic client options:
     *
     * - signature: Overrides the signature used by the client. Clients will always choose an appropriate default signature. However, it can be useful to override this with a custom setting. This can be set to "v4", "v3https", "v2" or an instance of Aws\Common\Signature\SignatureInterface.
     * - ssl.certificate_authority: Set to true to use the bundled CA cert or pass the full path to an SSL certificate bundle
     * - curl.options: Associative of CURLOPT_* cURL options to add to each request
     * - client.backoff.logger: `Guzzle\Log\LogAdapterInterface` object used to log backoff retries. Use 'debug' to emit PHP warnings when a retry is issued.
     * - client.backoff.logger.template: Optional template to use for exponential backoff log messages. See `Guzzle\Plugin\Backoff\BackoffLogger` for formatting information.
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
