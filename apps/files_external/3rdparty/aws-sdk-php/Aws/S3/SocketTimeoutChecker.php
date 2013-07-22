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

namespace Aws\S3;

use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffStrategyInterface;
use Guzzle\Plugin\Backoff\AbstractBackoffStrategy;

/**
 * Custom S3 exponential backoff checking use to retry 400 responses containing the following reason phrase:
 * "Your socket connection to the server was not read from or written to within the timeout period.".
 * This error has been reported as intermittent/random, and in most cases, seems to occur during the middle of a
 * transfer. This plugin will attempt to retry these failed requests, and if using a local file, will clear the
 * stat cache of the file and set a new content-length header on the upload.
 */
class SocketTimeoutChecker extends AbstractBackoffStrategy
{
    const ERR = 'Your socket connection to the server was not read from or written to within the timeout period';

    /**
     * {@inheridoc}
     */
    public function __construct(BackoffStrategyInterface $next = null)
    {
        if ($next) {
            $this->setNext($next);
        }
    }

    /**
     * {@inheridoc}
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
        if ($response
            && $response->getStatusCode() == 400
            && strpos($response->getBody(), self::ERR)
        ) {
            // Check if the request is sending a local file, and if so, clear the stat cache and recalculate the size.
            if ($request instanceof EntityEnclosingRequestInterface) {
                if ($request->getBody()->getWrapper() == 'plainfile') {
                    $filename = $request->getBody()->getUri();
                    // Clear the cache so that we send accurate file sizes
                    clearstatcache(true, $filename);
                    $length = filesize($filename);
                    $request->getBody()->setSize($length);
                    $request->setHeader('Content-Length', $length);
                }
            }

            return true;
        }
    }
}
