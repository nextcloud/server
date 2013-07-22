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

namespace Aws\S3\Exception\Parser;

use Aws\Common\Exception\Parser\DefaultXmlExceptionParser;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Parses S3 exception responses
 */
class S3ExceptionParser extends DefaultXmlExceptionParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(RequestInterface $request, Response $response)
    {
        $data = parent::parse($request, $response);

        if ($response->getStatusCode() === 301) {
            $data['type'] = 'client';
            if (isset($data['message'], $data['parsed'])) {
                $data['message'] = rtrim($data['message'], '.') . ': "' . $data['parsed']->Endpoint . '".';
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseHeaders(RequestInterface $request, Response $response, array &$data)
    {
        parent::parseHeaders($request, $response, $data);

        // Get the request
        $status  = $response->getStatusCode();
        $method  = $request->getMethod();

        // Attempt to determine code for 403s and 404s
        if ($status === 403) {
            $data['code'] = 'AccessDenied';
        } elseif ($method === 'HEAD' && $status === 404) {
            $path   = explode('/', trim($request->getPath(), '/'));
            $host   = explode('.', $request->getHost());
            $bucket = (count($host) === 4) ? $host[0] : array_shift($path);
            $object = array_shift($path);

            if ($bucket && $object) {
                $data['code'] = 'NoSuchKey';
            } elseif ($bucket) {
                $data['code'] = 'NoSuchBucket';
            }
        }
    }
}
