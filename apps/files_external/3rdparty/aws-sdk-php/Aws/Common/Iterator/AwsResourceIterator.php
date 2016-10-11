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

namespace Aws\Common\Iterator;

use Aws\Common\Enum\UaString as Ua;
use Aws\Common\Exception\RuntimeException;
Use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIterator;

/**
 * Iterate over a client command
 */
class AwsResourceIterator extends ResourceIterator
{
    /**
     * @var Model Result of a command
     */
    protected $lastResult = null;

    /**
     * Provides access to the most recent result obtained by the iterator. This makes it easier to extract any
     * additional information from the result which you do not have access to from the values emitted by the iterator
     *
     * @return Model|null
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    /**
     * {@inheritdoc}
     * This AWS specific version of the resource iterator provides a default implementation of the typical AWS iterator
     * process. It relies on configuration and extension to implement the operation-specific logic of handling results
     * and nextTokens. This method will loop until resources are acquired or there are no more iterations available.
     */
    protected function sendRequest()
    {
        do {
            // Prepare the request including setting the next token
            $this->prepareRequest();
            if ($this->nextToken) {
                $this->applyNextToken();
            }

            // Execute the request and handle the results
            $this->command->add(Ua::OPTION, Ua::ITERATOR);
            $this->lastResult = $this->command->getResult();
            $resources = $this->handleResults($this->lastResult);
            $this->determineNextToken($this->lastResult);

            // If no resources collected, prepare to reiterate before yielding
            if ($reiterate = empty($resources) && $this->nextToken) {
                $this->command = clone $this->originalCommand;
            }
        } while ($reiterate);

        return $resources;
    }

    protected function prepareRequest()
    {
        // Get the limit parameter key to set
        $limitKey = $this->get('limit_key');
        if ($limitKey && ($limit = $this->command->get($limitKey))) {
            $pageSize = $this->calculatePageSize();

            // If the limit of the command is different than the pageSize of the iterator, use the smaller value
            if ($limit && $pageSize) {
                $realLimit = min($limit, $pageSize);
                $this->command->set($limitKey, $realLimit);
            }
        }
    }

    protected function handleResults(Model $result)
    {
        $results = array();

        // Get the result key that contains the results
        if ($resultKey = $this->get('result_key')) {
            $results = $this->getValueFromResult($result, $resultKey) ?: array();
        }

        return $results;
    }

    protected function applyNextToken()
    {
        // Get the token parameter key to set
        if ($tokenParam = $this->get('input_token')) {
            // Set the next token. Works with multi-value tokens
            if (is_array($tokenParam)) {
                if (is_array($this->nextToken) && count($tokenParam) === count($this->nextToken)) {
                    foreach (array_combine($tokenParam, $this->nextToken) as $param => $token) {
                        $this->command->set($param, $token);
                    }
                } else {
                    throw new RuntimeException('The definition of the iterator\'s token parameter and the actual token '
                        . 'value are not compatible.');
                }
            } else {
                $this->command->set($tokenParam, $this->nextToken);
            }
        }
    }

    protected function determineNextToken(Model $result)
    {
        $this->nextToken = null;

        // If the value of "more_results" is true or there is no "more_results" to check, then try to get the next token
        $moreKey = $this->get('more_results');
        if ($moreKey === null || $this->getValueFromResult($result, $moreKey)) {
            // Get the token key to check
            if ($tokenKey = $this->get('output_token')) {
                // Get the next token's value. Works with multi-value tokens
                if (is_array($tokenKey)) {
                    $this->nextToken = array();
                    foreach ($tokenKey as $key) {
                        $this->nextToken[] = $this->getValueFromResult($result, $key);
                    }
                } else {
                    $this->nextToken = $this->getValueFromResult($result, $tokenKey);
                }
            }
        }
    }

    /**
     * Extracts the value from the result using Collection::getPath. Also adds some additional logic for keys that need
     * to access n-1 indexes (e.g., ImportExport, Kinesis). The n-1 logic only works for the known cases. We will switch
     * to a jmespath implementation in the future to cover all cases
     *
     * @param Model  $result
     * @param string $key
     *
     * @return mixed|null
     */
    protected function getValueFromResult(Model $result, $key)
    {
        // Special handling for keys that need to access n-1 indexes
        if (strpos($key, '#') !== false) {
            $keyParts = explode('#', $key, 2);
            $items = $result->getPath(trim($keyParts[0], '/'));
            if ($items && is_array($items)) {
                $index = count($items) - 1;
                $key = strtr($key, array('#' => $index));
            }
        }

        // Get the value
        return $result->getPath($key);
    }
}
