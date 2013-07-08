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

namespace Aws\Glacier;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Client\UploadBodyListener;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\Parser\JsonRestExceptionParser;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with Amazon Glacier
 *
 * @method Model abortMultipartUpload(array $args = array()) {@command Glacier AbortMultipartUpload}
 * @method Model completeMultipartUpload(array $args = array()) {@command Glacier CompleteMultipartUpload}
 * @method Model createVault(array $args = array()) {@command Glacier CreateVault}
 * @method Model deleteArchive(array $args = array()) {@command Glacier DeleteArchive}
 * @method Model deleteVault(array $args = array()) {@command Glacier DeleteVault}
 * @method Model deleteVaultNotifications(array $args = array()) {@command Glacier DeleteVaultNotifications}
 * @method Model describeJob(array $args = array()) {@command Glacier DescribeJob}
 * @method Model describeVault(array $args = array()) {@command Glacier DescribeVault}
 * @method Model getJobOutput(array $args = array()) {@command Glacier GetJobOutput}
 * @method Model getVaultNotifications(array $args = array()) {@command Glacier GetVaultNotifications}
 * @method Model initiateJob(array $args = array()) {@command Glacier InitiateJob}
 * @method Model initiateMultipartUpload(array $args = array()) {@command Glacier InitiateMultipartUpload}
 * @method Model listJobs(array $args = array()) {@command Glacier ListJobs}
 * @method Model listMultipartUploads(array $args = array()) {@command Glacier ListMultipartUploads}
 * @method Model listParts(array $args = array()) {@command Glacier ListParts}
 * @method Model listVaults(array $args = array()) {@command Glacier ListVaults}
 * @method Model setVaultNotifications(array $args = array()) {@command Glacier SetVaultNotifications}
 * @method Model uploadArchive(array $args = array()) {@command Glacier UploadArchive}
 * @method Model uploadMultipartPart(array $args = array()) {@command Glacier UploadMultipartPart}
 * @method waitUntilVaultExists(array $input) Wait until a vault can be accessed. The input array uses the parameters of the DescribeVault operation and waiter specific settings
 * @method waitUntilVaultNotExists(array $input) Wait until a vault is deleted. The input array uses the parameters of the DescribeVault operation and waiter specific settings
 * @method ResourceIteratorInterface getListJobsIterator(array $args = array()) The input array uses the parameters of the ListJobs operation
 * @method ResourceIteratorInterface getListMultipartUploadsIterator(array $args = array()) The input array uses the parameters of the ListMultipartUploads operation
 * @method ResourceIteratorInterface getListPartsIterator(array $args = array()) The input array uses the parameters of the ListParts operation
 * @method ResourceIteratorInterface getListVaultsIterator(array $args = array()) The input array uses the parameters of the ListVaults operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-glacier.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.Glacier.GlacierClient.html API docs
 */
class GlacierClient extends AbstractClient
{
    const LATEST_API_VERSION = '2012-06-01';

    /**
     * Factory method to create a new Amazon Glacier client using an array of configuration options:
     *
     * Credential options (`key`, `secret`, and optional `token` OR `credentials` is required)
     *
     * - key: AWS Access Key ID
     * - secret: AWS secret access key
     * - credentials: You can optionally provide a custom `Aws\Common\Credentials\CredentialsInterface` object
     * - token: Custom AWS security token to use with request authentication
     * - token.ttd: UNIX timestamp for when the custom credentials expire
     * - credentials.cache: Used to cache credentials when using providers that require HTTP requests. Set the true
     *   to use the default APC cache or provide a `Guzzle\Common\Cache\CacheAdapterInterface` object.
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
     * - client.backoff.logger: `Guzzle\Common\Log\LogAdapterInterface` object used to log backoff retries. Use
     *   'debug' to emit PHP warnings when a retry is issued.
     * - client.backoff.logger.template: Optional template to use for exponential backoff log messages. See
     *   `Guzzle\Http\Plugin\ExponentialBackoffLogger` for formatting information.
     *
     * @param array|Collection $config Client configuration data
     *
     * @return self
     */
    public static function factory($config = array())
    {
        // Setup the Glacier client
        $client = ClientBuilder::factory(__NAMESPACE__)
            ->setConfig($config)
            ->setConfigDefaults(array(
                Options::VERSION             => self::LATEST_API_VERSION,
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/glacier-%s.php',
                // Set default value for "accountId" for all requests
                'command.params' => array(
                    'accountId'               => '-',
                    Options::MODEL_PROCESSING => true
                )
            ))
            ->setExceptionParser(new JsonRestExceptionParser())
            ->setIteratorsConfig(array(
                'limit_param' => 'limit',
                'token_param' => 'marker',
                'token_key'   => 'Marker',
                'operations'  => array(
                    'ListJobs' => array(
                        'result_key' => 'JobList'
                    ),
                    'ListMultipartUploads' => array(
                        'result_key' => 'UploadsList'
                    ),
                    'ListParts' => array(
                        'result_key' => 'Parts'
                    ),
                    'ListVaults' => array(
                        'result_key' => 'VaultList'
                    )
                )
            ))
            ->build();

        // Add the Glacier version header required for all operations
        $client->getConfig()->setPath(
            'request.options/headers/x-amz-glacier-version',
            $client->getDescription()->getApiVersion()
        );

        // Allow for specifying bodies with file paths and file handles
        $uploadOperations = array('UploadArchive', 'UploadMultipartPart');
        $client->addSubscriber(new UploadBodyListener($uploadOperations, 'body', 'sourceFile'));

        // Listen for upload operations and make sure the required hash headers are added
        $client->addSubscriber(new GlacierUploadListener());

        return $client;
    }
}
