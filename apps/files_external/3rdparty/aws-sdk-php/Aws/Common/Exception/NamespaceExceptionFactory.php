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

namespace Aws\Common\Exception;

use Aws\Common\Exception\Parser\ExceptionParserInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Attempts to create exceptions by inferring the name from the code and a base
 * namespace that contains exceptions.  Exception classes are expected to be in
 * upper camelCase and always end in 'Exception'. 'Exception' will be appended
 * if it is not present in the exception code.
 */
class NamespaceExceptionFactory implements ExceptionFactoryInterface
{
    /**
     * @var ExceptionParserInterface $parser Parser used to parse responses
     */
    protected $parser;

    /**
     * @var string Base namespace containing exception classes
     */
    protected $baseNamespace;

    /**
     * @var string Default class to instantiate if a match is not found
     */
    protected $defaultException;

    /**
     * @param ExceptionParserInterface $parser           Parser used to parse exceptions
     * @param string                   $baseNamespace    Namespace containing exceptions
     * @param string                   $defaultException Default class to use if one is not mapped
     */
    public function __construct(
        ExceptionParserInterface $parser,
        $baseNamespace,
        $defaultException = 'Aws\Common\Exception\ServiceResponseException'
    ) {
        $this->parser = $parser;
        $this->baseNamespace = $baseNamespace;
        $this->defaultException = $defaultException;
    }

    /**
     * {@inheritdoc}
     */
    public function fromResponse(RequestInterface $request, Response $response)
    {
        $parts = $this->parser->parse($request, $response);

        // Removing leading 'AWS.' and embedded periods
        $className = $this->baseNamespace . '\\' . str_replace(array('AWS.', '.'), '', $parts['code']);
        if (substr($className, -9) !== 'Exception') {
            $className .= 'Exception';
        }

        $className = class_exists($className) ? $className : $this->defaultException;

        return $this->createException($className, $request, $response, $parts);
    }

    /**
     * Create an prepare an exception object
     *
     * @param string           $className Name of the class to create
     * @param RequestInterface $request   Request
     * @param Response         $response  Response received
     * @param array            $parts     Parsed exception data
     *
     * @return \Exception
     */
    protected function createException($className, RequestInterface $request, Response $response, array $parts)
    {
        $class = new $className($parts['message']);

        if ($class instanceof ServiceResponseException) {
            $class->setExceptionCode($parts['code']);
            $class->setExceptionType($parts['type']);
            $class->setResponse($response);
            $class->setRequest($request);
            $class->setRequestId($parts['request_id']);
        }

        return $class;
    }
}
