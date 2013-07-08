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

namespace Aws\DataPipeline;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Credentials\Credentials;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\Parser\JsonQueryExceptionParser;
use Guzzle\Common\Collection;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with AWS Data Pipeline
 *
 * @method Model activatePipeline(array $args = array()) {@command DataPipeline ActivatePipeline}
 * @method Model createPipeline(array $args = array()) {@command DataPipeline CreatePipeline}
 * @method Model deletePipeline(array $args = array()) {@command DataPipeline DeletePipeline}
 * @method Model describeObjects(array $args = array()) {@command DataPipeline DescribeObjects}
 * @method Model describePipelines(array $args = array()) {@command DataPipeline DescribePipelines}
 * @method Model evaluateExpression(array $args = array()) {@command DataPipeline EvaluateExpression}
 * @method Model getPipelineDefinition(array $args = array()) {@command DataPipeline GetPipelineDefinition}
 * @method Model listPipelines(array $args = array()) {@command DataPipeline ListPipelines}
 * @method Model pollForTask(array $args = array()) {@command DataPipeline PollForTask}
 * @method Model putPipelineDefinition(array $args = array()) {@command DataPipeline PutPipelineDefinition}
 * @method Model queryObjects(array $args = array()) {@command DataPipeline QueryObjects}
 * @method Model reportTaskProgress(array $args = array()) {@command DataPipeline ReportTaskProgress}
 * @method Model reportTaskRunnerHeartbeat(array $args = array()) {@command DataPipeline ReportTaskRunnerHeartbeat}
 * @method Model setStatus(array $args = array()) {@command DataPipeline SetStatus}
 * @method Model setTaskStatus(array $args = array()) {@command DataPipeline SetTaskStatus}
 * @method Model validatePipelineDefinition(array $args = array()) {@command DataPipeline ValidatePipelineDefinition}
 * @method ResourceIteratorInterface getListPipelinesIterator(array $args = array()) The input array uses the parameters of the ListPipelines operation
 * @method ResourceIteratorInterface getDescribeObjectsIterator(array $args = array()) The input array uses the parameters of the DescribeObjects operation
 * @method ResourceIteratorInterface getQueryObjectsIterator(array $args = array()) The input array uses the parameters of the QueryObjects operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-datapipeline.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.DataPipeline.DataPipelineClient.html API docs
 */
class DataPipelineClient extends AbstractClient
{
    const LATEST_API_VERSION = '2012-10-29';

    /**
     * Factory method to create a new Amazon Data Pipeline client using an array of configuration options:
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
     */
    public static function factory($config = array())
    {
        // Construct the Data Pipeline client with the client builder
        $client = ClientBuilder::factory(__NAMESPACE__)
            ->setConfig($config)
            ->setConfigDefaults(array(
                Options::VERSION             => self::LATEST_API_VERSION,
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/datapipeline-%s.php'
            ))
            ->setExceptionParser(new JsonQueryExceptionParser())
            ->setIteratorsConfig(array(
                'limit_key'   => 'limit',
                'more_key'    => 'hasMoreResults',
                'token_param' => 'marker',
                'token_key'   => 'marker',
                'operations'  => array(
                    'ListPipelines' => array(
                        'result_key'  => 'pipelineIdList',
                    ),
                    'DescribeObjects' => array(
                        'result_key'  => 'pipelineObjects',
                    ),
                    'QueryObjects' => array(
                        'result_key'  => 'ids',
                    ),
                )
            ))
            ->build();

        return $client;
    }
}
