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

use Aws\Common\Signature\SignatureInterface;
use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Http\Message\RequestInterface;

/**
 * Amazon S3 signature interface
 * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/RESTAuthentication.html
 */
interface S3SignatureInterface extends SignatureInterface
{
    /**
     * Sign a string for Amazon S3
     *
     * @param string               $string      String to sign
     * @param CredentialsInterface $credentials Credentials used to sign
     *
     * @return string
     */
    public function signString($string, CredentialsInterface $credentials);

    /**
     * Create a canonicalized string for a signature.
     *
     * @param RequestInterface $request Base on the request
     * @param string           $expires Pass a UNIX timestamp if creating a query signature
     *
     * @return string Returns a canonicalized string for an Amazon S3 signature.
     */
    public function createCanonicalizedString(RequestInterface $request, $expires = null);
}
