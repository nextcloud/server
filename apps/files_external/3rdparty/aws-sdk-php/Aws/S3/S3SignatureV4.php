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

use Aws\Common\Signature\SignatureV4;
use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestInterface;

/**
 * Amazon S3 signature version 4 overrides.
 */
class S3SignatureV4 extends SignatureV4 implements S3SignatureInterface
{
    /**
     * Always add a x-amz-content-sha-256 for data integrity.
     */
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        if (!$request->hasHeader('x-amz-content-sha256')) {
            $request->setHeader('x-amz-content-sha256', $this->getPresignedPayload($request));
        }

        parent::signRequest($request, $credentials);
    }

    /**
     * Override used to allow pre-signed URLs to be created for an
     * in-determinate request payload.
     */
    protected function getPresignedPayload(RequestInterface $request)
    {
        $result = parent::getPresignedPayload($request);

        // If the body is empty, then sign with 'UNSIGNED-PAYLOAD'
        if ($result === self::DEFAULT_PAYLOAD) {
            $result = hash('sha256', 'UNSIGNED-PAYLOAD');
        }

        return $result;
    }

    /**
     * Amazon S3 does not double-encode the path component in the canonical req
     */
    protected function createCanonicalizedPath(RequestInterface $request)
    {
        return '/' . ltrim($request->getPath(), '/');
    }
}
