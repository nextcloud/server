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

use Aws\Common\Exception\Parser\ExceptionParserInterface;
use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffStrategyInterface;
use Guzzle\Plugin\Backoff\AbstractBackoffStrategy;

/**
 * Backoff logic that handles throttling exceptions from services
 */
class ThrottlingErrorChecker extends AbstractBackoffStrategy
{
    /** @var array Whitelist of exception codes (as indexes) that indicate throttling */
    protected static $throttlingExceptions = array(
        'RequestLimitExceeded'                   => true,
        'Throttling'                             => true,
        'ThrottlingException'                    => true,
        'ProvisionedThroughputExceededException' => true,
        'RequestThrottled'                       => true,
    );

    /**
     * @var ExceptionParserInterface Exception parser used to parse exception responses
     */
    protected $exceptionParser;

    public function __construct(ExceptionParserInterface $exceptionParser, BackoffStrategyInterface $next = null)
    {
        $this->exceptionParser = $exceptionParser;
        if ($next) {
            $this->setNext($next);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function makesDecision()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDelay(
        $retries,
        RequestInterface $request,
        Response $response = null,
        HttpException $e = null
    ) {
        if ($response && $response->isClientError()) {
            $parts = $this->exceptionParser->parse($request, $response);
            return isset(self::$throttlingExceptions[$parts['code']]) ? true : null;
        }
    }
}
