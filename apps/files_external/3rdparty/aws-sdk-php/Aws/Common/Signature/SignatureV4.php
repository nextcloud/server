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
use Aws\Common\HostNameUtils;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestFactory;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;

/**
 * Signature Version 4
 * @link http://docs.aws.amazon.com/general/latest/gr/signature-version-4.html
 */
class SignatureV4 extends AbstractSignature implements EndpointSignatureInterface
{
    /** @var string Cache of the default empty entity-body payload */
    const DEFAULT_PAYLOAD = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    /** @var string Explicitly set service name */
    protected $serviceName;

    /** @var string Explicitly set region name */
    protected $regionName;

    /** @var int Maximum number of hashes to cache */
    protected $maxCacheSize = 50;

    /** @var array Cache of previously signed values */
    protected $hashCache = array();

    /** @var int Size of the hash cache */
    protected $cacheSize = 0;

    /**
     * @param string $serviceName Bind the signing to a particular service name
     * @param string $regionName  Bind the signing to a particular region name
     */
    public function __construct($serviceName = null, $regionName = null)
    {
        $this->serviceName = $serviceName;
        $this->regionName = $regionName;
    }

    /**
     * Set the service name instead of inferring it from a request URL
     *
     * @param string $service Name of the service used when signing
     *
     * @return self
     */
    public function setServiceName($service)
    {
        $this->serviceName = $service;

        return $this;
    }

    /**
     * Set the region name instead of inferring it from a request URL
     *
     * @param string $region Name of the region used when signing
     *
     * @return self
     */
    public function setRegionName($region)
    {
        $this->regionName = $region;

        return $this;
    }

    /**
     * Set the maximum number of computed hashes to cache
     *
     * @param int $maxCacheSize Maximum number of hashes to cache
     *
     * @return self
     */
    public function setMaxCacheSize($maxCacheSize)
    {
        $this->maxCacheSize = $maxCacheSize;

        return $this;
    }

    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        $timestamp = $this->getTimestamp();
        $longDate = gmdate(DateFormat::ISO8601, $timestamp);
        $shortDate = substr($longDate, 0, 8);

        // Remove any previously set Authorization headers so that retries work
        $request->removeHeader('Authorization');

        // Requires a x-amz-date header or Date
        if ($request->hasHeader('x-amz-date') || !$request->hasHeader('Date')) {
            $request->setHeader('x-amz-date', $longDate);
        } else {
            $request->setHeader('Date', gmdate(DateFormat::RFC1123, $timestamp));
        }

        // Add the security token if one is present
        if ($credentials->getSecurityToken()) {
            $request->setHeader('x-amz-security-token', $credentials->getSecurityToken());
        }

        // Parse the service and region or use one that is explicitly set
        $region = $this->regionName;
        $service = $this->serviceName;
        if (!$region || !$service) {
            $url = Url::factory($request->getUrl());
            $region = $region ?: HostNameUtils::parseRegionName($url);
            $service = $service ?: HostNameUtils::parseServiceName($url);
        }

        $credentialScope = $this->createScope($shortDate, $region, $service);
        $payload = $this->getPayload($request);
        $signingContext = $this->createSigningContext($request, $payload);
        $signingContext['string_to_sign'] = $this->createStringToSign(
            $longDate,
            $credentialScope,
            $signingContext['canonical_request']
        );

        // Calculate the signing key using a series of derived keys
        $signingKey = $this->getSigningKey($shortDate, $region, $service, $credentials->getSecretKey());
        $signature = hash_hmac('sha256', $signingContext['string_to_sign'], $signingKey);

        $request->setHeader('Authorization', "AWS4-HMAC-SHA256 "
            . "Credential={$credentials->getAccessKeyId()}/{$credentialScope}, "
            . "SignedHeaders={$signingContext['signed_headers']}, Signature={$signature}");

        // Add debug information to the request
        $request->getParams()->set('aws.signature', $signingContext);
    }

    public function createPresignedUrl(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires
    ) {
        $request = $this->createPresignedRequest($request, $credentials);
        $query = $request->getQuery();
        $httpDate = gmdate(DateFormat::ISO8601, $this->getTimestamp());
        $shortDate = substr($httpDate, 0, 8);
        $scope = $this->createScope(
            $shortDate,
            $this->regionName,
            $this->serviceName
        );
        $this->addQueryValues($scope, $request, $credentials, $expires);
        $payload = $this->getPresignedPayload($request);
        $context = $this->createSigningContext($request, $payload);
        $stringToSign = $this->createStringToSign(
            $httpDate,
            $scope,
            $context['canonical_request']
        );
        $key = $this->getSigningKey(
            $shortDate,
            $this->regionName,
            $this->serviceName,
            $credentials->getSecretKey()
        );
        $query['X-Amz-Signature'] = hash_hmac('sha256', $stringToSign, $key);

        return $request->getUrl();
    }

    /**
     * Converts a POST request to a GET request by moving POST fields into the
     * query string.
     *
     * Useful for pre-signing query protocol requests.
     *
     * @param EntityEnclosingRequestInterface $request Request to clone
     *
     * @return RequestInterface
     * @throws \InvalidArgumentException if the method is not POST
     */
    public static function convertPostToGet(EntityEnclosingRequestInterface $request)
    {
        if ($request->getMethod() !== 'POST') {
            throw new \InvalidArgumentException('Expected a POST request but '
                . 'received a ' . $request->getMethod() . ' request.');
        }

        $cloned = RequestFactory::getInstance()
            ->cloneRequestWithMethod($request, 'GET');

        // Move POST fields to the query if they are present
        foreach ($request->getPostFields() as $name => $value) {
            $cloned->getQuery()->set($name, $value);
        }

        return $cloned;
    }

    /**
     * Get the payload part of a signature from a request.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function getPayload(RequestInterface $request)
    {
        // Calculate the request signature payload
        if ($request->hasHeader('x-amz-content-sha256')) {
            // Handle streaming operations (e.g. Glacier.UploadArchive)
            return (string) $request->getHeader('x-amz-content-sha256');
        }

        if ($request instanceof EntityEnclosingRequestInterface) {
            return hash(
                'sha256',
                $request->getMethod() == 'POST' && count($request->getPostFields())
                    ? (string) $request->getPostFields()
                    : (string) $request->getBody()
            );
        }

        return self::DEFAULT_PAYLOAD;
    }

    /**
     * Get the payload of a request for use with pre-signed URLs.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function getPresignedPayload(RequestInterface $request)
    {
        return $this->getPayload($request);
    }

    protected function createCanonicalizedPath(RequestInterface $request)
    {
        $doubleEncoded = rawurlencode(ltrim($request->getPath(), '/'));

        return '/' . str_replace('%2F', '/', $doubleEncoded);
    }

    private function createStringToSign($longDate, $credentialScope, $creq)
    {
        return "AWS4-HMAC-SHA256\n{$longDate}\n{$credentialScope}\n"
            . hash('sha256', $creq);
    }

    private function createPresignedRequest(
        RequestInterface $request,
        CredentialsInterface $credentials
    ) {
        $sr = RequestFactory::getInstance()->cloneRequestWithMethod($request, 'GET');

        // Move POST fields to the query if they are present
        if ($request instanceof EntityEnclosingRequestInterface) {
            foreach ($request->getPostFields() as $name => $value) {
                $sr->getQuery()->set($name, $value);
            }
        }

        // Make sure to handle temporary credentials
        if ($token = $credentials->getSecurityToken()) {
            $sr->setHeader('X-Amz-Security-Token', $token);
            $sr->getQuery()->set('X-Amz-Security-Token', $token);
        }

        $this->moveHeadersToQuery($sr);

        return $sr;
    }

    /**
     * Create the canonical representation of a request
     *
     * @param RequestInterface $request Request to canonicalize
     * @param string           $payload Request payload (typically the value
     *                                  of the x-amz-content-sha256 header.
     *
     * @return array Returns an array of context information including:
     *               - canonical_request
     *               - signed_headers
     */
    private function createSigningContext(RequestInterface $request, $payload)
    {
        $signable = array(
            'host'        => true,
            'date'        => true,
            'content-md5' => true
        );

        // Normalize the path as required by SigV4 and ensure it's absolute
        $canon = $request->getMethod() . "\n"
            . $this->createCanonicalizedPath($request) . "\n"
            . $this->getCanonicalizedQueryString($request) . "\n";

        $canonHeaders = array();

        foreach ($request->getHeaders()->getAll() as $key => $values) {
            $key = strtolower($key);
            if (isset($signable[$key]) || substr($key, 0, 6) === 'x-amz-') {
                $values = $values->toArray();
                if (count($values) == 1) {
                    $values = $values[0];
                } else {
                    sort($values);
                    $values = implode(',', $values);
                }
                $canonHeaders[$key] = $key . ':' . preg_replace('/\s+/', ' ', $values);
            }
        }

        ksort($canonHeaders);
        $signedHeadersString = implode(';', array_keys($canonHeaders));
        $canon .= implode("\n", $canonHeaders) . "\n\n"
            . $signedHeadersString . "\n"
            . $payload;

        return array(
            'canonical_request' => $canon,
            'signed_headers'    => $signedHeadersString
        );
    }

    /**
     * Get a hash for a specific key and value.  If the hash was previously
     * cached, return it
     *
     * @param string $shortDate Short date
     * @param string $region    Region name
     * @param string $service   Service name
     * @param string $secretKey Secret Access Key
     *
     * @return string
     */
    private function getSigningKey($shortDate, $region, $service, $secretKey)
    {
        $cacheKey = $shortDate . '_' . $region . '_' . $service . '_' . $secretKey;

        // Retrieve the hash form the cache or create it and add it to the cache
        if (!isset($this->hashCache[$cacheKey])) {
            // When the cache size reaches the max, then just clear the cache
            if (++$this->cacheSize > $this->maxCacheSize) {
                $this->hashCache = array();
                $this->cacheSize = 0;
            }
            $dateKey = hash_hmac('sha256', $shortDate, 'AWS4' . $secretKey, true);
            $regionKey = hash_hmac('sha256', $region, $dateKey, true);
            $serviceKey = hash_hmac('sha256', $service, $regionKey, true);
            $this->hashCache[$cacheKey] = hash_hmac('sha256', 'aws4_request', $serviceKey, true);
        }

        return $this->hashCache[$cacheKey];
    }

    /**
     * Get the canonicalized query string for a request
     *
     * @param  RequestInterface $request
     * @return string
     */
    private function getCanonicalizedQueryString(RequestInterface $request)
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
            } elseif ($values === 0) {
                $values = array('0');
            } elseif (!$values) {
                $values = array('');
            }

            foreach ((array) $values as $value) {
                if ($value === QueryString::BLANK) {
                    $value = '';
                }
                $qs .= rawurlencode($key) . '=' . rawurlencode($value) . '&';
            }
        }

        return substr($qs, 0, -1);
    }

    private function convertExpires($expires)
    {
        if ($expires instanceof \DateTime) {
            $expires = $expires->getTimestamp();
        } elseif (!is_numeric($expires)) {
            $expires = strtotime($expires);
        }

        $duration = $expires - time();

        // Ensure that the duration of the signature is not longer than a week
        if ($duration > 604800) {
            throw new \InvalidArgumentException('The expiration date of a '
                . 'signature version 4 presigned URL must be less than one '
                . 'week');
        }

        return $duration;
    }

    private function createScope($shortDate, $region, $service)
    {
        return $shortDate
            . '/' . $region
            . '/' . $service
            . '/aws4_request';
    }

    private function addQueryValues(
        $scope,
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires
    ) {
        $credential = $credentials->getAccessKeyId() . '/' . $scope;

        // Set query params required for pre-signed URLs
        $request->getQuery()
            ->set('X-Amz-Algorithm', 'AWS4-HMAC-SHA256')
            ->set('X-Amz-Credential', $credential)
            ->set('X-Amz-Date', gmdate('Ymd\THis\Z', $this->getTimestamp()))
            ->set('X-Amz-SignedHeaders', 'Host')
            ->set('X-Amz-Expires', $this->convertExpires($expires));
    }

    private function moveHeadersToQuery(RequestInterface $request)
    {
        $query = $request->getQuery();

        foreach ($request->getHeaders() as $name => $header) {
            if (substr($name, 0, 5) == 'x-amz') {
                $query[$header->getName()] = (string) $header;
            }
            if ($name !== 'host') {
                $request->removeHeader($name);
            }
        }
    }
}
