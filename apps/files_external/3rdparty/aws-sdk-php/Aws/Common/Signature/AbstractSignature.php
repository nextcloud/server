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

use Guzzle\Http\Message\RequestInterface;

/**
 * Abstract signature class that can be used when implementing new concrete
 * AWS signature protocol strategies
 */
abstract class AbstractSignature implements SignatureInterface
{
    /**
     * @var int Timestamp
     */
    private $timestamp;

    /**
     * Get the canonicalized query string for a request
     *
     * @param  RequestInterface $request
     * @return string
     */
    protected function getCanonicalizedQueryString(RequestInterface $request)
    {
        $queryParams = $request->getQuery()->getAll();
        unset($queryParams['X-Amz-Signature']);
        if (empty($queryParams)) {
            return '';
        }

        $qs = '';
        ksort($queryParams);
        foreach ($queryParams as $key => $values) {
            if (is_array($values)) {
                sort($values);
            } elseif (!$values) {
                $values = array('');
            }

            foreach ((array) $values as $value) {
                $qs .= rawurlencode($key) . '=' . rawurlencode($value) . '&';
            }
        }

        return substr($qs, 0, -1);
    }

    /**
     * Provides the timestamp used for the class
     *
     * @param bool $refresh Set to TRUE to refresh the cached timestamp
     *
     * @return int
     */
    protected function getTimestamp($refresh = false)
    {
        if (!$this->timestamp || $refresh) {
            $this->timestamp = time();
        }

        return $this->timestamp;
    }

    /**
     * Get a date for one of the parts of the requests
     *
     * @param string $format Date format
     *
     * @return string
     */
    protected function getDateTime($format)
    {
        return gmdate($format, $this->getTimestamp());
    }
}
