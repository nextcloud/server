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

use Aws\Common\Credentials\AbstractRefreshableCredentials;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\Parser\ExceptionParserInterface;
use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffStrategyInterface;
use Guzzle\Plugin\Backoff\AbstractBackoffStrategy;

/**
 * Backoff logic that handles retrying requests when credentials expire
 */
class ExpiredCredentialsChecker extends AbstractBackoffStrategy
{
    /**
     * @var array Array of known retrying exception codes
     */
    protected $retryable = array(
        'RequestExpired' => true,
        'ExpiredTokenException' => true,
        'ExpiredToken' => true
    );

    /**
     * @var ExceptionParserInterface Exception parser used to parse exception responses
     */
    protected $exceptionParser;

    public function __construct(ExceptionParserInterface $exceptionParser, BackoffStrategyInterface $next = null) {
        $this->exceptionParser = $exceptionParser;
        $this->next = $next;
    }

    public function makesDecision()
    {
        return true;
    }

    protected function getDelay($retries, RequestInterface $request, Response $response = null, HttpException $e = null)
    {
        if ($response && $response->isClientError()) {

            $parts = $this->exceptionParser->parse($request, $response);
            if (!isset($this->retryable[$parts['code']]) || !$request->getClient()) {
                return null;
            }

            /** @var $client AwsClientInterface */
            $client = $request->getClient();
            // Only retry if the credentials can be refreshed
            if (!($client->getCredentials() instanceof AbstractRefreshableCredentials)) {
                return null;
            }

            // Resign the request using new credentials
            $client->getSignature()->signRequest($request, $client->getCredentials()->setExpiration(-1));

            // Retry immediately with no delay
            return 0;
        }
    }
}
