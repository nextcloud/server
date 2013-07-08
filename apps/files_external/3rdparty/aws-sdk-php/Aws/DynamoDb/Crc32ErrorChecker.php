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

namespace Aws\DynamoDb;

use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Backoff\BackoffStrategyInterface;
use Guzzle\Plugin\Backoff\AbstractBackoffStrategy;
use Guzzle\Stream\Stream;

/**
 * Validates the x-amz-crc32 header of a response, and if corrupt, will retry the request
 */
class Crc32ErrorChecker extends AbstractBackoffStrategy
{
    /**
     * Create the internal parser
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
        if ($response) {
            // Validate the checksum against our computed checksum
            if ($checksum = (string) $response->getHeader('x-amz-crc32')) {
                // Retry the request if the checksums don't match, otherwise, return null
                return $checksum != hexdec(Stream::getHash($response->getBody(), 'crc32b')) ? true : null;
            }
        }
    }
}
