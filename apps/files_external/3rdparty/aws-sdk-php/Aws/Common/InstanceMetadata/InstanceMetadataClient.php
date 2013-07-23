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

namespace Aws\Common\InstanceMetadata;

use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\InstanceProfileCredentialsException;
use Aws\Common\Credentials\Credentials;
use Aws\Common\Client\AbstractClient;
use Guzzle\Common\Collection;
use Guzzle\Http\Message\RequestFactory;

/**
 * Client used for interacting with the Amazon EC2 instance metadata server
 */
class InstanceMetadataClient extends AbstractClient
{
    /**
     * Factory method to create a new InstanceMetadataClient using an array
     * of configuration options.
     *
     * The configuration options accepts the following array keys and values:
     * - base_url: Override the base URL of the instance metadata server
     * - version:  Version of the metadata server to interact with
     *
     * @param array|Collection $config Configuration options
     *
     * @return InstanceMetadataClient
     */
    public static function factory($config = array())
    {
        $config = Collection::fromConfig($config, array(
            Options::BASE_URL => 'http://169.254.169.254/{version}/',
            'version'         => 'latest',
        ), array('base_url', 'version'));

        return new self($config);
    }

    /**
     * Constructor override
     */
    public function __construct(Collection $config)
    {
        $this->setConfig($config);
        $this->setBaseUrl($config->get(Options::BASE_URL));
        $this->defaultHeaders = new Collection();
        $this->setRequestFactory(RequestFactory::getInstance());
    }

    /**
     * Get instance profile credentials
     *
     * @return Credentials
     * @throws InstanceProfileCredentialsException
     */
    public function getInstanceProfileCredentials()
    {
        try {
            $request = $this->get('meta-data/iam/security-credentials/');
            $request->getCurlOptions()->set(CURLOPT_TIMEOUT, 1)->set(CURLOPT_CONNECTTIMEOUT, 1);
            $credentials = trim($request->send()->getBody(true));
            $result = $this->get("meta-data/iam/security-credentials/{$credentials}")->send()->json();
        } catch (\Exception $e) {
            $message = 'Error retrieving credentials from the instance profile metadata server.  When you are not'
                . ' running inside of Amazon EC2, you must provide your AWS access key ID and secret access key in'
                . ' the "key" and "secret" options when creating a client or provide an instantiated'
                . ' Aws\\Common\\Credentials\\CredentialsInterface object.';
            throw new InstanceProfileCredentialsException($message, $e->getCode(), $e);
        }

        // Ensure that the status code was successful
        if ($result['Code'] !== 'Success') {
            $e = new InstanceProfileCredentialsException('Unexpected response code: ' . $result['Code']);
            $e->setStatusCode($result['Code']);
            throw $e;
        }

        return new Credentials(
            $result['AccessKeyId'],
            $result['SecretAccessKey'],
            $result['Token'],
            strtotime($result['Expiration'])
        );
    }
}
