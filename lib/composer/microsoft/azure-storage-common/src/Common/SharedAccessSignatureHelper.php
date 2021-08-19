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
 * @copyright Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common;

use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\Utilities;
use MicrosoftAzure\Storage\Common\Internal\Validate;

/**
 * Provides methods to generate Azure Storage Shared Access Signature
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class SharedAccessSignatureHelper
{
    protected $accountName;
    protected $accountKey;

    /**
     * Constructor.
     *
     * @param string $accountName the name of the storage account.
     * @param string $accountKey the shared key of the storage account
     *
     */
    public function __construct($accountName, $accountKey)
    {
        Validate::canCastAsString($accountName, 'accountName');
        Validate::notNullOrEmpty($accountName, 'accountName');

        Validate::canCastAsString($accountKey, 'accountKey');
        Validate::notNullOrEmpty($accountKey, 'accountKey');

        $this->accountName = urldecode($accountName);
        $this->accountKey = $accountKey;
    }

    /**
     * Generates a shared access signature at the account level.
     *
     * @param string $signedVersion           Specifies the signed version to use.
     * @param string $signedPermissions       Specifies the signed permissions for
     *                                        the account SAS.
     * @param string $signedService           Specifies the signed services
     *                                        accessible with the account SAS.
     * @param string $signedResourceType      Specifies the signed resource types
     *                                        that are accessible with the account
     *                                        SAS.
     * @param \Datetime|string $signedExpiry  The time at which the shared access
     *                                        signature becomes invalid, in an ISO
     *                                        8601 format.
     * @param \Datetime|string $signedStart   The time at which the SAS becomes
     *                                        valid, in an ISO 8601 format.
     * @param string $signedIP                Specifies an IP address or a range
     *                                        of IP addresses from which to accept
     *                                        requests.
     * @param string $signedProtocol          Specifies the protocol permitted for
     *                                        a request made with the account SAS.
     *
     * @see Constructing an account SAS at
     *      https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/constructing-an-account-sas
     *
     * @return string
     */
    public function generateAccountSharedAccessSignatureToken(
        $signedVersion,
        $signedPermissions,
        $signedService,
        $signedResourceType,
        $signedExpiry,
        $signedStart = "",
        $signedIP = "",
        $signedProtocol = ""
    ) {
        // check that version is valid
        Validate::canCastAsString($signedVersion, 'signedVersion');
        Validate::notNullOrEmpty($signedVersion, 'signedVersion');
        Validate::isDateString($signedVersion, 'signedVersion');

        // validate and sanitize signed service
        $signedService = $this->validateAndSanitizeSignedService($signedService);

        // validate and sanitize signed resource type
        $signedResourceType = $this->validateAndSanitizeSignedResourceType($signedResourceType);

        // validate and sanitize signed permissions
        $signedPermissions = $this->validateAndSanitizeSignedPermissions($signedPermissions);

        // check that expiracy is valid
        if ($signedExpiry instanceof \Datetime) {
            $signedExpiry = Utilities::isoDate($signedExpiry);
        }
        Validate::canCastAsString($signedExpiry, 'signedExpiry');
        Validate::notNullOrEmpty($signedExpiry, 'signedExpiry');
        Validate::isDateString($signedExpiry, 'signedExpiry');

        // check that signed start is valid
        if ($signedStart instanceof \Datetime) {
            $signedStart = Utilities::isoDate($signedStart);
        }
        Validate::canCastAsString($signedStart, 'signedStart');
        if (strlen($signedStart) > 0) {
            Validate::isDateString($signedStart, 'signedStart');
        }

        // check that signed IP is valid
        Validate::canCastAsString($signedIP, 'signedIP');

        // validate and sanitize signed protocol
        $signedProtocol = $this->validateAndSanitizeSignedProtocol($signedProtocol);

        // construct an array with the parameters to generate the shared access signature at the account level
        $parameters = array();
        $parameters[] = $this->accountName;
        $parameters[] = $signedPermissions;
        $parameters[] = $signedService;
        $parameters[] = $signedResourceType;
        $parameters[] = $signedStart;
        $parameters[] = $signedExpiry;
        $parameters[] = $signedIP;
        $parameters[] = $signedProtocol;
        $parameters[] = $signedVersion;

        // implode the parameters into a string
        $stringToSign = utf8_encode(implode("\n", $parameters) . "\n");

        // decode the account key from base64
        $decodedAccountKey = base64_decode($this->accountKey);

        // create the signature with hmac sha256
        $signature = hash_hmac("sha256", $stringToSign, $decodedAccountKey, true);

        // encode the signature as base64 and url encode.
        $sig = urlencode(base64_encode($signature));

        //adding all the components for account SAS together.
        $sas  = 'sv=' . $signedVersion;
        $sas .= '&ss=' . $signedService;
        $sas .= '&srt=' . $signedResourceType;
        $sas .= '&sp=' . $signedPermissions;
        $sas .= '&se=' . $signedExpiry;
        $sas .= $signedStart === ''? '' : '&st=' . $signedStart;
        $sas .= $signedIP === ''? '' : '&sip=' . $signedIP;
        $sas .= '&spr=' . $signedProtocol;
        $sas .= '&sig=' . $sig;

        // return the signature
        return $sas;
    }

    /**
     * Validates and sanitizes the signed service parameter
     *
     * @param string $signedService Specifies the signed services accessible
     *                              with the account SAS.
     *
     * @return string
     */
    protected function validateAndSanitizeSignedService($signedService)
    {
        // validate signed service is not null or empty
        Validate::canCastAsString($signedService, 'signedService');
        Validate::notNullOrEmpty($signedService, 'signedService');

        // The signed service should only be a combination of the letters b(lob) q(ueue) t(able) or f(ile)
        $validServices = ['b', 'q', 't', 'f'];

        return $this->validateAndSanitizeStringWithArray(
            strtolower($signedService),
            $validServices
        );
    }

    /**
     * Validates and sanitizes the signed resource type parameter
     *
     * @param string $signedResourceType    Specifies the signed resource types
     *                                      that are accessible with the account
     *                                      SAS.
     *
     * @return string
     */
    protected function validateAndSanitizeSignedResourceType($signedResourceType)
    {
        // validate signed resource type is not null or empty
        Validate::canCastAsString($signedResourceType, 'signedResourceType');
        Validate::notNullOrEmpty($signedResourceType, 'signedResourceType');

        // The signed resource type should only be a combination of the letters s(ervice) c(container) or o(bject)
        $validResourceTypes = ['s', 'c', 'o'];

        return $this->validateAndSanitizeStringWithArray(
            strtolower($signedResourceType),
            $validResourceTypes
        );
    }

    /**
     * Validates and sanitizes the signed permissions parameter
     *
     * @param string $signedPermissions Specifies the signed permissions for the
     *                                  account SAS.
     *
     * @return string
     */
    protected function validateAndSanitizeSignedPermissions(
        $signedPermissions
    ) {
        // validate signed permissions are not null or empty
        Validate::canCastAsString($signedPermissions, 'signedPermissions');
        Validate::notNullOrEmpty($signedPermissions, 'signedPermissions');

        $validPermissions = ['r', 'w', 'd', 'l', 'a', 'c', 'u', 'p'];

        return $this->validateAndSanitizeStringWithArray(
            strtolower($signedPermissions),
            $validPermissions
        );
    }

    /**
     * Validates and sanitizes the signed protocol parameter
     *
     * @param string $signedProtocol Specifies the signed protocol for the
     *                               account SAS.

     * @return string
     */
    protected function validateAndSanitizeSignedProtocol($signedProtocol)
    {
        Validate::canCastAsString($signedProtocol, 'signedProtocol');
        // sanitize string
        $sanitizedSignedProtocol = strtolower($signedProtocol);
        if (strlen($sanitizedSignedProtocol) > 0) {
            if (strcmp($sanitizedSignedProtocol, "https") != 0 && strcmp($sanitizedSignedProtocol, "https,http") != 0) {
                throw new \InvalidArgumentException(Resources::SIGNED_PROTOCOL_INVALID_VALIDATION_MSG);
            }
        }

        return $sanitizedSignedProtocol;
    }

    /**
     * Removes duplicate characters from a string
     *
     * @param string $input        The input string.

     * @return string
     */
    protected function validateAndSanitizeStringWithArray($input, array $array)
    {
        $result = '';
        foreach ($array as $value) {
            if (strpos($input, $value) !== false) {
                //append the valid permission to result.
                $result .= $value;
                //remove all the character that represents the permission.
                $input = str_replace(
                    $value,
                    '',
                    $input
                );
            }
        }

        Validate::isTrue(
            strlen($input) == '',
            sprintf(
                Resources::STRING_NOT_WITH_GIVEN_COMBINATION,
                implode(', ', $array)
            )
        );
        return $result;
    }


    /**
     * Generate the canonical resource using the given account name, service
     * type and resource.
     *
     * @param  string $accountName The account name of the service.
     * @param  string $service     The service name of the service.
     * @param  string $resource    The name of the resource.
     *
     * @return string
     */
    protected static function generateCanonicalResource(
        $accountName,
        $service,
        $resource
    ) {
        static $serviceMap = array(
            Resources::RESOURCE_TYPE_BLOB  => 'blob',
            Resources::RESOURCE_TYPE_FILE  => 'file',
            Resources::RESOURCE_TYPE_QUEUE => 'queue',
            Resources::RESOURCE_TYPE_TABLE => 'table',
        );
        $serviceName = $serviceMap[$service];
        if (Utilities::startsWith($resource, '/')) {
            $resource = substr($resource, 1);
        }
        return urldecode(sprintf('/%s/%s/%s', $serviceName, $accountName, $resource));
    }
}
