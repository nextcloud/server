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

namespace Aws\Common\Exception\Parser;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Interface used to parse exceptions into an associative array of data
 */
interface ExceptionParserInterface
{
    /**
     * Parses an exception into an array of data containing at minimum the
     * following array keys:
     * - type:       Exception type
     * - code:       Exception code
     * - message:    Exception message
     * - request_id: Request ID
     * - parsed:     The parsed representation of the data (array, SimpleXMLElement, etc)
     *
     * @param RequestInterface $request
     * @param Response         $response Unsuccessful response
     *
     * @return array
     */
    public function parse(RequestInterface $request, Response $response);
}
