<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal\Authentication
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal\Authentication;

use GuzzleHttp\Psr7\Request;
use MicrosoftAzure\Storage\Common\Internal\Http\HttpFormatter;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\Utilities;

/**
 * Provides shared key authentication scheme for blob and queue. For more info
 * check: http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal\Authentication
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class SharedKeyAuthScheme implements IAuthScheme
{
    /**
     * The account name
     */
    protected $accountName;

    /**
     * The account key
     */
    protected $accountKey;

    /**
     * The included headers
     */
    protected $includedHeaders;

    /**
     * Constructor.
     *
     * @param string $accountName storage account name.
     * @param string $accountKey  storage account primary or secondary key.
     *
     * @return SharedKeyAuthScheme
     */
    public function __construct($accountName, $accountKey)
    {
        $this->accountKey  = $accountKey;
        $this->accountName = $accountName;

        $this->includedHeaders   = array();
        $this->includedHeaders[] = Resources::CONTENT_ENCODING;
        $this->includedHeaders[] = Resources::CONTENT_LANGUAGE;
        $this->includedHeaders[] = Resources::CONTENT_LENGTH;
        $this->includedHeaders[] = Resources::CONTENT_MD5;
        $this->includedHeaders[] = Resources::CONTENT_TYPE;
        $this->includedHeaders[] = Resources::DATE;
        $this->includedHeaders[] = Resources::IF_MODIFIED_SINCE;
        $this->includedHeaders[] = Resources::IF_MATCH;
        $this->includedHeaders[] = Resources::IF_NONE_MATCH;
        $this->includedHeaders[] = Resources::IF_UNMODIFIED_SINCE;
        $this->includedHeaders[] = Resources::RANGE;
    }

    /**
     * Computes the authorization signature for blob and queue shared key.
     *
     * @param array  $headers     request headers.
     * @param string $url         reuqest url.
     * @param array  $queryParams query variables.
     * @param string $httpMethod  request http method.
     *
     * @see Blob and Queue Services (Shared Key Authentication) at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return string
     */
    protected function computeSignature(
        array $headers,
        $url,
        array $queryParams,
        $httpMethod
    ) {
        $canonicalizedHeaders = $this->computeCanonicalizedHeaders($headers);

        $canonicalizedResource = $this->computeCanonicalizedResource(
            $url,
            $queryParams
        );

        $stringToSign   = array();
        $stringToSign[] = strtoupper($httpMethod);

        foreach ($this->includedHeaders as $header) {
            $stringToSign[] = Utilities::tryGetValueInsensitive($header, $headers);
        }

        if (count($canonicalizedHeaders) > 0) {
            $stringToSign[] = implode("\n", $canonicalizedHeaders);
        }

        $stringToSign[] = $canonicalizedResource;
        $stringToSign   = implode("\n", $stringToSign);

        return $stringToSign;
    }

    /**
     * Returns authorization header to be included in the request.
     *
     * @param array  $headers     request headers.
     * @param string $url         reuqest url.
     * @param array  $queryParams query variables.
     * @param string $httpMethod  request http method.
     *
     * @see Specifying the Authorization Header section at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return string
     */
    public function getAuthorizationHeader(
        array $headers,
        $url,
        array $queryParams,
        $httpMethod
    ) {
        $signature = $this->computeSignature(
            $headers,
            $url,
            $queryParams,
            $httpMethod
        );

        return 'SharedKey ' . $this->accountName . ':' . base64_encode(
            hash_hmac('sha256', $signature, base64_decode($this->accountKey), true)
        );
    }

    /**
     * Computes canonicalized headers for headers array.
     *
     * @param array $headers request headers.
     *
     * @see Constructing the Canonicalized Headers String section at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return array
     */
    protected function computeCanonicalizedHeaders($headers)
    {
        $canonicalizedHeaders = array();
        $normalizedHeaders    = array();
        $validPrefix          =  Resources::X_MS_HEADER_PREFIX;

        if (is_null($normalizedHeaders)) {
            return $canonicalizedHeaders;
        }

        foreach ($headers as $header => $value) {
            // Convert header to lower case.
            $header = strtolower($header);

            // Retrieve all headers for the resource that begin with x-ms-,
            // including the x-ms-date header.
            if (Utilities::startsWith($header, $validPrefix)) {
                // Unfold the string by replacing any breaking white space
                // (meaning what splits the headers, which is \r\n) with a single
                // space.
                $value = str_replace("\r\n", ' ', $value);

                // Trim any white space around the colon in the header.
                $value  = ltrim($value);
                $header = rtrim($header);

                $normalizedHeaders[$header] = $value;
            }
        }

        // Sort the headers lexicographically by header name, in ascending order.
        // Note that each header may appear only once in the string.
        ksort($normalizedHeaders);

        foreach ($normalizedHeaders as $key => $value) {
            $canonicalizedHeaders[] = $key . ':' . $value;
        }

        return $canonicalizedHeaders;
    }

    /**
     * Computes canonicalized resources from URL using Table formar
     *
     * @param string $url         request url.
     * @param array  $queryParams request query variables.
     *
     * @see Constructing the Canonicalized Resource String section at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return string
     */
    protected function computeCanonicalizedResourceForTable($url, $queryParams)
    {
        $queryParams = array_change_key_case($queryParams);

        // 1. Beginning with an empty string (""), append a forward slash (/),
        //    followed by the name of the account that owns the accessed resource.
        $canonicalizedResource = '/' . $this->accountName;

        // 2. Append the resource's encoded URI path, without any query parameters.
        $canonicalizedResource .= parse_url($url, PHP_URL_PATH);

        // 3. The query string should include the question mark and the comp
        //    parameter (for example, ?comp=metadata). No other parameters should
        //    be included on the query string.
        if (array_key_exists(Resources::QP_COMP, $queryParams)) {
            $canonicalizedResource .= '?' . Resources::QP_COMP . '=';
            $canonicalizedResource .= $queryParams[Resources::QP_COMP];
        }

        return $canonicalizedResource;
    }

    /**
     * Computes canonicalized resources from URL.
     *
     * @param string $url         request url.
     * @param array  $queryParams request query variables.
     *
     * @see Constructing the Canonicalized Resource String section at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return string
     */
    protected function computeCanonicalizedResource($url, $queryParams)
    {
        $queryParams = array_change_key_case($queryParams);

        // 1. Beginning with an empty string (""), append a forward slash (/),
        //    followed by the name of the account that owns the accessed resource.
        $canonicalizedResource = '/' . $this->accountName;

        // 2. Append the resource's encoded URI path, without any query parameters.
        $canonicalizedResource .= parse_url($url, PHP_URL_PATH);

        // 3. Retrieve all query parameters on the resource URI, including the comp
        //    parameter if it exists.
        // 4. Sort the query parameters lexicographically by parameter name, in
        //    ascending order.
        if (count($queryParams) > 0) {
            ksort($queryParams);
        }

        // 5. Convert all parameter names to lowercase.
        // 6. URL-decode each query parameter name and value.
        // 7. Append each query parameter name and value to the string in the
        //    following format:
        //      parameter-name:parameter-value
        // 9. Group query parameters
        // 10. Append a new line character (\n) after each name-value pair.
        foreach ($queryParams as $key => $value) {
            // $value must already be ordered lexicographically
            // See: ServiceRestProxy::groupQueryValues
            $canonicalizedResource .= "\n" . $key . ':' . $value;
        }

        return $canonicalizedResource;
    }

    /**
     * Adds authentication header to the request headers.
     *
     * @param  \GuzzleHttp\Psr7\Request $request HTTP request object.
     *
     * @abstract
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    public function signRequest(Request $request)
    {
        $requestHeaders = HttpFormatter::formatHeaders($request->getHeaders());

        $signedKey = $this->getAuthorizationHeader(
            $requestHeaders,
            $request->getUri(),
            \GuzzleHttp\Psr7\parse_query(
                $request->getUri()->getQuery()
            ),
            $request->getMethod()
        );

        return $request->withHeader(Resources::AUTHENTICATION, $signedKey);
    }
}
