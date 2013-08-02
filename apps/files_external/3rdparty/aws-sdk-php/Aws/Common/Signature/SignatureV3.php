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

namespace Aws\Common\Signature;

use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Enum\DateFormat;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;

/**
 * Implementation of Signature Version 3
 * @link http://docs.amazonwebservices.com/amazonswf/latest/developerguide/HMACAuth-swf.html
 */
class SignatureV3 extends AbstractSignature
{
    /**
     * Get an array of headers to be signed
     *
     * @param RequestInterface $request Request to get headers from
     *
     * @return array
     */
    protected function getHeadersToSign(RequestInterface $request)
    {
        $headers = array();
        foreach ($request->getHeaders()->toArray() as $k => $v) {
            $k = strtolower($k);
            if ($k == 'host' || strpos($k, 'x-amz-') !== false) {
                $headers[$k] = implode(',', $v);
            }
        }

        // Sort the headers alphabetically and add them to the string to sign
        ksort($headers);

        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        // Refresh the cached timestamp
        $this->getTimestamp(true);

        // Add default headers
        $request->setHeader('x-amz-date', $this->getDateTime(DateFormat::RFC1123));

        // Add the security token if one is present
        if ($credentials->getSecurityToken()) {
            $request->setHeader('x-amz-security-token', $credentials->getSecurityToken());
        }

        // Grab the path and ensure that it is absolute
        $path = '/' . ltrim($request->getUrl(true)->normalizePath()->getPath(), '/');

        // Begin building the string to sign
        $sign = $request->getMethod() . "\n"
            . "{$path}\n"
            . $this->getCanonicalizedQueryString($request) . "\n";

        // Get all of the headers that must be signed (host and x-amz-*)
        $headers = $this->getHeadersToSign($request);
        foreach ($headers as $key => $value) {
            $sign .= $key . ':' . $value . "\n";
        }

        $sign .= "\n";

        // Add the body of the request if a body is present
        if ($request instanceof EntityEnclosingRequestInterface) {
            $sign .= (string) $request->getBody();
        }

        // Add the string to sign to the request for debugging purposes
        $request->getParams()->set('aws.string_to_sign', $sign);

        $signature = base64_encode(hash_hmac('sha256',
            hash('sha256', $sign, true), $credentials->getSecretKey(), true));

        // Add the authorization header to the request
        $request->setHeader('x-amzn-authorization', sprintf('AWS3 AWSAccessKeyId=%s,Algorithm=HmacSHA256,SignedHeaders=%s,Signature=%s',
            $credentials->getAccessKeyId(),
            implode(';', array_keys($headers)),
            $signature));
    }
}
