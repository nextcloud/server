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
 * @copyright 2019 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal\Authentication;

use GuzzleHttp\Psr7\Request;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\Validate;

/**
 * Azure authentication scheme for token credential.
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal\Authentication
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2019 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class TokenAuthScheme implements IAuthScheme
{
    /**
     * The authentication token
     */
    protected $tokenRef;

    /**
     * Constructor.
     *
     * @param string $token the token used for AAD authentication.
     */
    public function __construct(&$token)
    {
        $this->tokenRef =& $token;
    }

    /**
     * Adds authentication header to the request headers.
     *
     * @param \GuzzleHttp\Psr7\Request $request HTTP request object.
     *
     * @abstract
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    public function signRequest(Request $request)
    {
        $bearerToken = "Bearer ". $this->tokenRef;
        return $request->withHeader(Resources::AUTHENTICATION, $bearerToken);
    }
}
