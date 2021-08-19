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
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal;

/**
 * Represents the settings used to sign and access a request against the storage
 * service. For more information about storage service connection strings check this
 * page: http://msdn.microsoft.com/en-us/library/ee758697
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class StorageServiceSettings extends ServiceSettings
{
    private $name;
    private $key;
    private $sas;
    private $blobEndpointUri;
    private $queueEndpointUri;
    private $tableEndpointUri;
    private $fileEndpointUri;
    private $blobSecondaryEndpointUri;
    private $queueSecondaryEndpointUri;
    private $tableSecondaryEndpointUri;
    private $fileSecondaryEndpointUri;

    private static $devStoreAccount;
    private static $useDevelopmentStorageSetting;
    private static $developmentStorageProxyUriSetting;
    private static $defaultEndpointsProtocolSetting;
    private static $accountNameSetting;
    private static $accountKeySetting;
    private static $sasTokenSetting;
    private static $blobEndpointSetting;
    private static $queueEndpointSetting;
    private static $tableEndpointSetting;
    private static $fileEndpointSetting;
    private static $endpointSuffixSetting;

    /**
     * If initialized or not
     * @internal
     */
    protected static $isInitialized = false;

    /**
     * Valid setting keys
     * @internal
     */
    protected static $validSettingKeys = array();

    /**
     * Initializes static members of the class.
     *
     * @return void
     */
    protected static function init()
    {
        self::$useDevelopmentStorageSetting = self::setting(
            Resources::USE_DEVELOPMENT_STORAGE_NAME,
            'true'
        );

        self::$developmentStorageProxyUriSetting = self::settingWithFunc(
            Resources::DEVELOPMENT_STORAGE_PROXY_URI_NAME,
            Validate::getIsValidUri()
        );

        self::$defaultEndpointsProtocolSetting = self::setting(
            Resources::DEFAULT_ENDPOINTS_PROTOCOL_NAME,
            'http',
            'https'
        );

        self::$accountNameSetting = self::setting(Resources::ACCOUNT_NAME_NAME);

        self::$accountKeySetting = self::settingWithFunc(
            Resources::ACCOUNT_KEY_NAME,
            // base64_decode will return false if the $key is not in base64 format.
            function ($key) {
                $isValidBase64String = base64_decode($key, true);
                if ($isValidBase64String) {
                    return true;
                } else {
                    throw new \RuntimeException(
                        sprintf(Resources::INVALID_ACCOUNT_KEY_FORMAT, $key)
                    );
                }
            }
        );

        self::$sasTokenSetting = self::setting(Resources::SAS_TOKEN_NAME);

        self::$blobEndpointSetting = self::settingWithFunc(
            Resources::BLOB_ENDPOINT_NAME,
            Validate::getIsValidUri()
        );

        self::$queueEndpointSetting = self::settingWithFunc(
            Resources::QUEUE_ENDPOINT_NAME,
            Validate::getIsValidUri()
        );

        self::$tableEndpointSetting = self::settingWithFunc(
            Resources::TABLE_ENDPOINT_NAME,
            Validate::getIsValidUri()
        );

        self::$fileEndpointSetting = self::settingWithFunc(
            Resources::FILE_ENDPOINT_NAME,
            Validate::getIsValidUri()
        );

        self::$endpointSuffixSetting = self::settingWithFunc(
            Resources::ENDPOINT_SUFFIX_NAME,
            Validate::getIsValidHostname()
        );

        self::$validSettingKeys[] = Resources::USE_DEVELOPMENT_STORAGE_NAME;
        self::$validSettingKeys[] = Resources::DEVELOPMENT_STORAGE_PROXY_URI_NAME;
        self::$validSettingKeys[] = Resources::DEFAULT_ENDPOINTS_PROTOCOL_NAME;
        self::$validSettingKeys[] = Resources::ACCOUNT_NAME_NAME;
        self::$validSettingKeys[] = Resources::ACCOUNT_KEY_NAME;
        self::$validSettingKeys[] = Resources::SAS_TOKEN_NAME;
        self::$validSettingKeys[] = Resources::BLOB_ENDPOINT_NAME;
        self::$validSettingKeys[] = Resources::QUEUE_ENDPOINT_NAME;
        self::$validSettingKeys[] = Resources::TABLE_ENDPOINT_NAME;
        self::$validSettingKeys[] = Resources::FILE_ENDPOINT_NAME;
        self::$validSettingKeys[] = Resources::ENDPOINT_SUFFIX_NAME;
    }

    /**
     * Creates new storage service settings instance.
     *
     * @param string $name                      The storage service name.
     * @param string $key                       The storage service key.
     * @param string $blobEndpointUri           The storage service blob
     *                                          endpoint.
     * @param string $queueEndpointUri          The storage service queue
     *                                          endpoint.
     * @param string $tableEndpointUri          The storage service table
     *                                          endpoint.
     * @param string $fileEndpointUri           The storage service file
     *                                          endpoint.
     * @param string $blobSecondaryEndpointUri  The storage service secondary
     *                                          blob endpoint.
     * @param string $queueSecondaryEndpointUri The storage service secondary
     *                                          queue endpoint.
     * @param string $tableSecondaryEndpointUri The storage service secondary
     *                                          table endpoint.
     * @param string $fileSecondaryEndpointUri  The storage service secondary
     *                                          file endpoint.
     * @param string $sas                       The storage service SAS token.
     */
    public function __construct(
        $name,
        $key,
        $blobEndpointUri,
        $queueEndpointUri,
        $tableEndpointUri,
        $fileEndpointUri,
        $blobSecondaryEndpointUri = null,
        $queueSecondaryEndpointUri = null,
        $tableSecondaryEndpointUri = null,
        $fileSecondaryEndpointUri = null,
        $sas = null
    ) {
        $this->name                      = $name;
        $this->key                       = $key;
        $this->sas                       = $sas;
        $this->blobEndpointUri           = $blobEndpointUri;
        $this->queueEndpointUri          = $queueEndpointUri;
        $this->tableEndpointUri          = $tableEndpointUri;
        $this->fileEndpointUri           = $fileEndpointUri;
        $this->blobSecondaryEndpointUri  = $blobSecondaryEndpointUri;
        $this->queueSecondaryEndpointUri = $queueSecondaryEndpointUri;
        $this->tableSecondaryEndpointUri = $tableSecondaryEndpointUri;
        $this->fileSecondaryEndpointUri  = $fileSecondaryEndpointUri;
    }

    /**
     * Returns a StorageServiceSettings with development storage credentials using
     * the specified proxy Uri.
     *
     * @param string $proxyUri The proxy endpoint to use.
     *
     * @return StorageServiceSettings
     */
    private static function getDevelopmentStorageAccount($proxyUri)
    {
        if (is_null($proxyUri)) {
            return self::developmentStorageAccount();
        }

        $scheme = parse_url($proxyUri, PHP_URL_SCHEME);
        $host   = parse_url($proxyUri, PHP_URL_HOST);
        $prefix = $scheme . "://" . $host;

        return new StorageServiceSettings(
            Resources::DEV_STORE_NAME,
            Resources::DEV_STORE_KEY,
            $prefix . ':10000/devstoreaccount1/',
            $prefix . ':10001/devstoreaccount1/',
            $prefix . ':10002/devstoreaccount1/',
            null
        );
    }

    /**
     * Gets a StorageServiceSettings object that references the development storage
     * account.
     *
     * @return StorageServiceSettings
     */
    public static function developmentStorageAccount()
    {
        if (is_null(self::$devStoreAccount)) {
            self::$devStoreAccount = self::getDevelopmentStorageAccount(
                Resources::DEV_STORE_URI
            );
        }

        return self::$devStoreAccount;
    }

    /**
     * Gets the default service endpoint using the specified protocol and account
     * name.
     *
     * @param string $scheme      The scheme of the service end point.
     * @param string $accountName The account name of the service.
     * @param string $dnsPrefix   The service DNS prefix.
     * @param string $dnsSuffix   The service DNS suffix.
     * @param bool   $isSecondary If generating secondary endpoint.
     *
     * @return string
     */
    private static function getServiceEndpoint(
        $scheme,
        $accountName,
        $dnsPrefix,
        $dnsSuffix = null,
        $isSecondary = false
    ) {
        if ($isSecondary) {
            $accountName .= Resources::SECONDARY_STRING;
        }
        if ($dnsSuffix === null) {
            $dnsSuffix = Resources::DEFAULT_ENDPOINT_SUFFIX;
        }
        return sprintf(
            Resources::SERVICE_URI_FORMAT,
            $scheme,
            $accountName,
            $dnsPrefix.$dnsSuffix
        );
    }

    /**
     * Creates StorageServiceSettings object given endpoints uri.
     *
     * @param array  $settings                  The service settings.
     * @param string $blobEndpointUri           The blob endpoint uri.
     * @param string $queueEndpointUri          The queue endpoint uri.
     * @param string $tableEndpointUri          The table endpoint uri.
     * @param string $fileEndpointUri           The file endpoint uri.
     * @param string $blobSecondaryEndpointUri  The blob secondary endpoint uri.
     * @param string $queueSecondaryEndpointUri The queue secondary endpoint uri.
     * @param string $tableSecondaryEndpointUri The table secondary endpoint uri.
     * @param string $fileSecondaryEndpointUri  The file secondary endpoint uri.
     *
     * @return StorageServiceSettings
     */
    private static function createStorageServiceSettings(
        array $settings,
        $blobEndpointUri = null,
        $queueEndpointUri = null,
        $tableEndpointUri = null,
        $fileEndpointUri = null,
        $blobSecondaryEndpointUri = null,
        $queueSecondaryEndpointUri = null,
        $tableSecondaryEndpointUri = null,
        $fileSecondaryEndpointUri = null
    ) {
        $blobEndpointUri  = Utilities::tryGetValueInsensitive(
            Resources::BLOB_ENDPOINT_NAME,
            $settings,
            $blobEndpointUri
        );
        $queueEndpointUri = Utilities::tryGetValueInsensitive(
            Resources::QUEUE_ENDPOINT_NAME,
            $settings,
            $queueEndpointUri
        );
        $tableEndpointUri = Utilities::tryGetValueInsensitive(
            Resources::TABLE_ENDPOINT_NAME,
            $settings,
            $tableEndpointUri
        );
        $fileEndpointUri = Utilities::tryGetValueInsensitive(
            Resources::FILE_ENDPOINT_NAME,
            $settings,
            $fileEndpointUri
        );
        $accountName      = Utilities::tryGetValueInsensitive(
            Resources::ACCOUNT_NAME_NAME,
            $settings
        );
        $accountKey       = Utilities::tryGetValueInsensitive(
            Resources::ACCOUNT_KEY_NAME,
            $settings
        );
        $sasToken         = Utilities::tryGetValueInsensitive(
            Resources::SAS_TOKEN_NAME,
            $settings
        );

        return new StorageServiceSettings(
            $accountName,
            $accountKey,
            $blobEndpointUri,
            $queueEndpointUri,
            $tableEndpointUri,
            $fileEndpointUri,
            $blobSecondaryEndpointUri,
            $queueSecondaryEndpointUri,
            $tableSecondaryEndpointUri,
            $fileSecondaryEndpointUri,
            $sasToken
        );
    }

    /**
     * Creates a StorageServiceSettings object from the given connection string.
     *
     * @param string $connectionString The storage settings connection string.
     *
     * @return StorageServiceSettings
     */
    public static function createFromConnectionString($connectionString)
    {
        $tokenizedSettings = self::parseAndValidateKeys($connectionString);

        // Devstore case
        $matchedSpecs = self::matchedSpecification(
            $tokenizedSettings,
            self::allRequired(self::$useDevelopmentStorageSetting),
            self::optional(self::$developmentStorageProxyUriSetting)
        );
        if ($matchedSpecs) {
            $proxyUri = Utilities::tryGetValueInsensitive(
                Resources::DEVELOPMENT_STORAGE_PROXY_URI_NAME,
                $tokenizedSettings
            );

            return self::getDevelopmentStorageAccount($proxyUri);
        }

        // Automatic case
        $matchedSpecs = self::matchedSpecification(
            $tokenizedSettings,
            self::allRequired(
                self::$defaultEndpointsProtocolSetting,
                self::$accountNameSetting,
                self::$accountKeySetting
            ),
            self::optional(
                self::$blobEndpointSetting,
                self::$queueEndpointSetting,
                self::$tableEndpointSetting,
                self::$fileEndpointSetting,
                self::$endpointSuffixSetting
            )
        );
        if ($matchedSpecs) {
            $scheme         = Utilities::tryGetValueInsensitive(
                Resources::DEFAULT_ENDPOINTS_PROTOCOL_NAME,
                $tokenizedSettings
            );
            $accountName    = Utilities::tryGetValueInsensitive(
                Resources::ACCOUNT_NAME_NAME,
                $tokenizedSettings
            );
            $endpointSuffix = Utilities::tryGetValueInsensitive(
                Resources::ENDPOINT_SUFFIX_NAME,
                $tokenizedSettings
            );
            return self::createStorageServiceSettings(
                $tokenizedSettings,
                self::getServiceEndpoint(
                    $scheme,
                    $accountName,
                    Resources::BLOB_DNS_PREFIX,
                    $endpointSuffix
                ),
                self::getServiceEndpoint(
                    $scheme,
                    $accountName,
                    Resources::QUEUE_DNS_PREFIX,
                    $endpointSuffix
                ),
                self::getServiceEndpoint(
                    $scheme,
                    $accountName,
                    Resources::TABLE_DNS_PREFIX,
                    $endpointSuffix
                ),
                self::getServiceEndpoint(
                    $scheme,
                    $accountName,
                    Resources::FILE_DNS_PREFIX,
                    $endpointSuffix
                ),
                self::getServiceEndpoint(
                    $scheme,
                    $accountName,
                    Resources::BLOB_DNS_PREFIX,
                    $endpointSuffix,
                    true
                ),
                self::getServiceEndpoint(
                    $scheme,
                    $accountName,
                    Resources::QUEUE_DNS_PREFIX,
                    $endpointSuffix,
                    true
                ),
                self::getServiceEndpoint(
                    $scheme,
                    $accountName,
                    Resources::TABLE_DNS_PREFIX,
                    $endpointSuffix,
                    true
                ),
                self::getServiceEndpoint(
                    $scheme,
                    $accountName,
                    Resources::FILE_DNS_PREFIX,
                    $endpointSuffix,
                    true
                )
            );
        }

        // Explicit case for AccountName/AccountKey combination
        $matchedSpecs = self::matchedSpecification(
            $tokenizedSettings,
            self::atLeastOne(
                self::$blobEndpointSetting,
                self::$queueEndpointSetting,
                self::$tableEndpointSetting,
                self::$fileEndpointSetting
            ),
            self::allRequired(
                self::$accountNameSetting,
                self::$accountKeySetting
            )
        );
        if ($matchedSpecs) {
            return self::createStorageServiceSettings($tokenizedSettings);
        }

        // Explicit case for SAS token
        $matchedSpecs = self::matchedSpecification(
            $tokenizedSettings,
            self::atLeastOne(
                self::$blobEndpointSetting,
                self::$queueEndpointSetting,
                self::$tableEndpointSetting,
                self::$fileEndpointSetting
            ),
            self::allRequired(
                self::$sasTokenSetting
            )
        );
        if ($matchedSpecs) {
            return self::createStorageServiceSettings($tokenizedSettings);
        }

        self::noMatch($connectionString);
    }

    /**
     * Creates a StorageServiceSettings object from the given connection string.
     * Note this is only for AAD connection string, it should at least contain
     * the account name.
     *
     * @param string $connectionString The storage settings connection string.
     *
     * @return StorageServiceSettings
     */
    public static function createFromConnectionStringForTokenCredential($connectionString)
    {
        // Explicit case for AAD token, Connection string could only have account
        // name.
        $tokenizedSettings = self::parseAndValidateKeys($connectionString);

        $scheme         = Utilities::tryGetValueInsensitive(
            Resources::DEFAULT_ENDPOINTS_PROTOCOL_NAME,
            $tokenizedSettings
        );
        $accountName    = Utilities::tryGetValueInsensitive(
            Resources::ACCOUNT_NAME_NAME,
            $tokenizedSettings
        );
        $endpointSuffix = Utilities::tryGetValueInsensitive(
            Resources::ENDPOINT_SUFFIX_NAME,
            $tokenizedSettings
        );
        return self::createStorageServiceSettings(
            $tokenizedSettings,
            self::getServiceEndpoint(
                $scheme,
                $accountName,
                Resources::BLOB_DNS_PREFIX,
                $endpointSuffix
            ),
            self::getServiceEndpoint(
                $scheme,
                $accountName,
                Resources::QUEUE_DNS_PREFIX,
                $endpointSuffix
            ),
            self::getServiceEndpoint(
                $scheme,
                $accountName,
                Resources::TABLE_DNS_PREFIX,
                $endpointSuffix
            ),
            self::getServiceEndpoint(
                $scheme,
                $accountName,
                Resources::FILE_DNS_PREFIX,
                $endpointSuffix
            ),
            self::getServiceEndpoint(
                $scheme,
                $accountName,
                Resources::BLOB_DNS_PREFIX,
                $endpointSuffix,
                true
            ),
            self::getServiceEndpoint(
                $scheme,
                $accountName,
                Resources::QUEUE_DNS_PREFIX,
                $endpointSuffix,
                true
            ),
            self::getServiceEndpoint(
                $scheme,
                $accountName,
                Resources::TABLE_DNS_PREFIX,
                $endpointSuffix,
                true
            ),
            self::getServiceEndpoint(
                $scheme,
                $accountName,
                Resources::FILE_DNS_PREFIX,
                $endpointSuffix,
                true
            )
        );
    }

    /**
     * Gets storage service name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets storage service key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Checks if there is a SAS token.
     *
     * @return boolean
     */
    public function hasSasToken()
    {
        return !empty($this->sas);
    }

    /**
     * Gets storage service SAS token.
     *
     * @return string
     */
    public function getSasToken()
    {
        return $this->sas;
    }

    /**
     * Gets storage service blob endpoint uri.
     *
     * @return string
     */
    public function getBlobEndpointUri()
    {
        return $this->blobEndpointUri;
    }

    /**
     * Gets storage service queue endpoint uri.
     *
     * @return string
     */
    public function getQueueEndpointUri()
    {
        return $this->queueEndpointUri;
    }

    /**
     * Gets storage service table endpoint uri.
     *
     * @return string
     */
    public function getTableEndpointUri()
    {
        return $this->tableEndpointUri;
    }

    /**
     * Gets storage service file endpoint uri.
     *
     * @return string
     */
    public function getFileEndpointUri()
    {
        return $this->fileEndpointUri;
    }

    /**
     * Gets storage service secondary blob endpoint uri.
     *
     * @return string
     */
    public function getBlobSecondaryEndpointUri()
    {
        return $this->blobSecondaryEndpointUri;
    }

    /**
     * Gets storage service secondary queue endpoint uri.
     *
     * @return string
     */
    public function getQueueSecondaryEndpointUri()
    {
        return $this->queueSecondaryEndpointUri;
    }

    /**
     * Gets storage service secondary table endpoint uri.
     *
     * @return string
     */
    public function getTableSecondaryEndpointUri()
    {
        return $this->tableSecondaryEndpointUri;
    }

    /**
     * Gets storage service secondary file endpoint uri.
     *
     * @return string
     */
    public function getFileSecondaryEndpointUri()
    {
        return $this->fileSecondaryEndpointUri;
    }
}
