<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

use OC\ServerNotAvailableException;
use OCA\User_LDAP\Exceptions\ConfigurationIssueException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * magic properties
 * responsible for LDAP connections in context with the provided configuration
 *
 * @property string $ldapHost
 * @property string $ldapPort
 * @property string $ldapBackupHost
 * @property string $ldapBackupPort
 * @property string $ldapBackgroundHost
 * @property string $ldapBackgroundPort
 * @property array|'' $ldapBase
 * @property array|'' $ldapBaseUsers
 * @property array|'' $ldapBaseGroups
 * @property string $ldapAgentName
 * @property string $ldapAgentPassword
 * @property string $ldapTLS
 * @property string $turnOffCertCheck
 * @property string $ldapIgnoreNamingRules
 * @property string $ldapUserDisplayName
 * @property string $ldapUserDisplayName2
 * @property string $ldapUserAvatarRule
 * @property string $ldapGidNumber
 * @property array|'' $ldapUserFilterObjectclass
 * @property array|'' $ldapUserFilterGroups
 * @property string $ldapUserFilter
 * @property string $ldapUserFilterMode
 * @property string $ldapGroupFilter
 * @property string $ldapGroupFilterMode
 * @property array|'' $ldapGroupFilterObjectclass
 * @property array|'' $ldapGroupFilterGroups
 * @property string $ldapGroupDisplayName
 * @property string $ldapGroupMemberAssocAttr
 * @property string $ldapLoginFilter
 * @property string $ldapLoginFilterMode
 * @property string $ldapLoginFilterEmail
 * @property string $ldapLoginFilterUsername
 * @property array|'' $ldapLoginFilterAttributes
 * @property string $ldapQuotaAttribute
 * @property string $ldapQuotaDefault
 * @property string $ldapEmailAttribute
 * @property string $ldapCacheTTL
 * @property string $ldapUuidUserAttribute
 * @property string $ldapUuidGroupAttribute
 * @property string $ldapOverrideMainServer
 * @property string $ldapConfigurationActive
 * @property array|'' $ldapAttributesForUserSearch
 * @property array|'' $ldapAttributesForGroupSearch
 * @property string $ldapExperiencedAdmin
 * @property string $homeFolderNamingRule
 * @property string $hasMemberOfFilterSupport
 * @property string $useMemberOfToDetectMembership
 * @property string $ldapExpertUsernameAttr
 * @property string $ldapExpertUUIDUserAttr
 * @property string $ldapExpertUUIDGroupAttr
 * @property string $markRemnantsAsDisabled
 * @property string $lastJpegPhotoLookup
 * @property string $ldapNestedGroups
 * @property string $ldapPagingSize
 * @property string $turnOnPasswordChange
 * @property string $ldapDynamicGroupMemberURL
 * @property string $ldapDefaultPPolicyDN
 * @property string $ldapExtStorageHomeAttribute
 * @property string $ldapMatchingRuleInChainState
 * @property string $ldapConnectionTimeout
 * @property string $ldapAttributePhone
 * @property string $ldapAttributeWebsite
 * @property string $ldapAttributeAddress
 * @property string $ldapAttributeTwitter
 * @property string $ldapAttributeFediverse
 * @property string $ldapAttributeOrganisation
 * @property string $ldapAttributeRole
 * @property string $ldapAttributeHeadline
 * @property string $ldapAttributeBiography
 * @property string $ldapAdminGroup
 * @property string $ldapAttributeBirthDate
 * @property string $ldapAttributePronouns
 */
class Connection extends LDAPUtility {
	private ?\LDAP\Connection $ldapConnectionRes = null;
	private bool $configured = false;

	/**
	 * @var bool whether connection should be kept on __destruct
	 */
	private bool $dontDestruct = false;

	/**
	 * @var bool runtime flag that indicates whether supported primary groups are available
	 */
	public $hasPrimaryGroups = true;

	/**
	 * @var bool runtime flag that indicates whether supported POSIX gidNumber are available
	 */
	public $hasGidNumber = true;

	/**
	 * @var ICache|null
	 */
	protected $cache = null;

	/** @var Configuration settings handler * */
	protected $configuration;

	/**
	 * @var bool
	 */
	protected $doNotValidate = false;

	/**
	 * @var bool
	 */
	protected $ignoreValidation = false;

	/**
	 * @var array{sum?: string, result?: bool}
	 */
	protected $bindResult = [];

	protected LoggerInterface $logger;
	private IL10N $l10n;

	/**
	 * Constructor
	 * @param string $configPrefix a string with the prefix for the configkey column (appconfig table)
	 * @param string|null $configID a string with the value for the appid column (appconfig table) or null for on-the-fly connections
	 */
	public function __construct(
		ILDAPWrapper $ldap,
		private string $configPrefix = '',
		private ?string $configID = 'user_ldap',
	) {
		parent::__construct($ldap);
		$this->configuration = new Configuration($this->configPrefix, !is_null($this->configID));
		$memcache = Server::get(ICacheFactory::class);
		if ($memcache->isAvailable()) {
			$this->cache = $memcache->createDistributed();
		}
		$helper = Server::get(Helper::class);
		$this->doNotValidate = !in_array($this->configPrefix,
			$helper->getServerConfigurationPrefixes());
		$this->logger = Server::get(LoggerInterface::class);
		$this->l10n = Util::getL10N('user_ldap');
	}

	public function __destruct() {
		if (!$this->dontDestruct && $this->ldap->isResource($this->ldapConnectionRes)) {
			@$this->ldap->unbind($this->ldapConnectionRes);
			$this->bindResult = [];
		}
	}

	/**
	 * defines behaviour when the instance is cloned
	 */
	public function __clone() {
		$this->configuration = new Configuration($this->configPrefix,
			!is_null($this->configID));
		if (count($this->bindResult) !== 0 && $this->bindResult['result'] === true) {
			$this->bindResult = [];
		}
		$this->ldapConnectionRes = null;
		$this->dontDestruct = true;
	}

	public function __get(string $name) {
		if (!$this->configured) {
			$this->readConfiguration();
		}

		return $this->configuration->$name;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$this->doNotValidate = false;
		$before = $this->configuration->$name;
		$this->configuration->$name = $value;
		$after = $this->configuration->$name;
		if ($before !== $after) {
			if ($this->configID !== '' && $this->configID !== null) {
				$this->configuration->saveConfiguration();
			}
			$this->validateConfiguration();
		}
	}

	/**
	 * @param string $rule
	 * @return array
	 * @throws \RuntimeException
	 */
	public function resolveRule($rule) {
		return $this->configuration->resolveRule($rule);
	}

	/**
	 * sets whether the result of the configuration validation shall
	 * be ignored when establishing the connection. Used by the Wizard
	 * in early configuration state.
	 * @param bool $state
	 */
	public function setIgnoreValidation($state) {
		$this->ignoreValidation = (bool)$state;
	}

	/**
	 * initializes the LDAP backend
	 * @param bool $force read the config settings no matter what
	 */
	public function init($force = false) {
		$this->readConfiguration($force);
		$this->establishConnection();
	}

	/**
	 * @return \LDAP\Connection The LDAP resource
	 */
	public function getConnectionResource(): \LDAP\Connection {
		if (!$this->ldapConnectionRes) {
			$this->init();
		}
		if (is_null($this->ldapConnectionRes)) {
			$this->logger->error(
				'No LDAP Connection to server ' . $this->configuration->ldapHost,
				['app' => 'user_ldap']
			);
			throw new ServerNotAvailableException('Connection to LDAP server could not be established');
		}
		return $this->ldapConnectionRes;
	}

	/**
	 * resets the connection resource
	 */
	public function resetConnectionResource(): void {
		if (!is_null($this->ldapConnectionRes)) {
			@$this->ldap->unbind($this->ldapConnectionRes);
			$this->ldapConnectionRes = null;
			$this->bindResult = [];
		}
	}

	/**
	 * @param string|null $key
	 */
	private function getCacheKey($key): string {
		$prefix = 'LDAP-' . $this->configID . '-' . $this->configPrefix . '-';
		if (is_null($key)) {
			return $prefix;
		}
		return $prefix . hash('sha256', $key);
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getFromCache($key) {
		if (!$this->configured) {
			$this->readConfiguration();
		}
		if (is_null($this->cache) || !$this->configuration->ldapCacheTTL) {
			return null;
		}
		$key = $this->getCacheKey($key);

		return json_decode(base64_decode($this->cache->get($key) ?? ''), true);
	}

	public function getConfigPrefix(): string {
		return $this->configPrefix;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function writeToCache($key, $value, ?int $ttlOverride = null): void {
		if (!$this->configured) {
			$this->readConfiguration();
		}
		if (is_null($this->cache)
			|| !$this->configuration->ldapCacheTTL
			|| !$this->configuration->ldapConfigurationActive) {
			return;
		}
		$key = $this->getCacheKey($key);
		$value = base64_encode(json_encode($value));
		$ttl = $ttlOverride ?? $this->configuration->ldapCacheTTL;
		$this->cache->set($key, $value, $ttl);
	}

	public function clearCache() {
		if (!is_null($this->cache)) {
			$this->cache->clear($this->getCacheKey(null));
		}
	}

	/**
	 * Caches the general LDAP configuration.
	 * @param bool $force optional. true, if the re-read should be forced. defaults
	 *                    to false.
	 */
	private function readConfiguration(bool $force = false): void {
		if ((!$this->configured || $force) && !is_null($this->configID)) {
			$this->configuration->readConfiguration();
			$this->configured = $this->validateConfiguration();
		}
	}

	/**
	 * set LDAP configuration with values delivered by an array, not read from configuration
	 * @param array $config array that holds the config parameters in an associated array
	 * @param array &$setParameters optional; array where the set fields will be given to
	 * @param bool $throw if true, throw ConfigurationIssueException with details instead of returning false
	 * @return bool true if config validates, false otherwise. Check with $setParameters for detailed success on single parameters
	 */
	public function setConfiguration(array $config, ?array &$setParameters = null, bool $throw = false): bool {
		if (is_null($setParameters)) {
			$setParameters = [];
		}
		$this->doNotValidate = false;
		$this->configuration->setConfiguration($config, $setParameters);
		if (count($setParameters) > 0) {
			$this->configured = $this->validateConfiguration($throw);
		}


		return $this->configured;
	}

	/**
	 * saves the current Configuration in the database and empties the
	 * cache
	 * @return null
	 */
	public function saveConfiguration() {
		$this->configuration->saveConfiguration();
		$this->clearCache();
	}

	/**
	 * get the current LDAP configuration
	 * @return array
	 */
	public function getConfiguration() {
		$this->readConfiguration();
		$config = $this->configuration->getConfiguration();
		$cta = $this->configuration->getConfigTranslationArray();
		$result = [];
		foreach ($cta as $dbkey => $configkey) {
			switch ($configkey) {
				case 'homeFolderNamingRule':
					if (str_starts_with($config[$configkey], 'attr:')) {
						$result[$dbkey] = substr($config[$configkey], 5);
					} else {
						$result[$dbkey] = '';
					}
					break;
				case 'ldapBase':
				case 'ldapBaseUsers':
				case 'ldapBaseGroups':
				case 'ldapAttributesForUserSearch':
				case 'ldapAttributesForGroupSearch':
					if (is_array($config[$configkey])) {
						$result[$dbkey] = implode("\n", $config[$configkey]);
						break;
					} //else follows default
					// no break
				default:
					$result[$dbkey] = $config[$configkey];
			}
		}
		return $result;
	}

	private function doSoftValidation(): void {
		//if User or Group Base are not set, take over Base DN setting
		foreach (['ldapBaseUsers', 'ldapBaseGroups'] as $keyBase) {
			$val = $this->configuration->$keyBase;
			if (empty($val)) {
				$this->configuration->$keyBase = $this->configuration->ldapBase;
			}
		}

		foreach (['ldapExpertUUIDUserAttr' => 'ldapUuidUserAttribute',
			'ldapExpertUUIDGroupAttr' => 'ldapUuidGroupAttribute'] as $expertSetting => $effectiveSetting) {
			$uuidOverride = $this->configuration->$expertSetting;
			if (!empty($uuidOverride)) {
				$this->configuration->$effectiveSetting = $uuidOverride;
			} else {
				$uuidAttributes = Access::UUID_ATTRIBUTES;
				array_unshift($uuidAttributes, 'auto');
				if (!in_array($this->configuration->$effectiveSetting, $uuidAttributes)
					&& !is_null($this->configID)) {
					$this->configuration->$effectiveSetting = 'auto';
					$this->configuration->saveConfiguration();
					$this->logger->info(
						'Illegal value for the ' . $effectiveSetting . ', reset to autodetect.',
						['app' => 'user_ldap']
					);
				}
			}
		}

		$backupPort = (int)$this->configuration->ldapBackupPort;
		if ($backupPort <= 0) {
			$this->configuration->ldapBackupPort = $this->configuration->ldapPort;
		}

		//make sure empty search attributes are saved as simple, empty array
		$saKeys = ['ldapAttributesForUserSearch',
			'ldapAttributesForGroupSearch'];
		foreach ($saKeys as $key) {
			$val = $this->configuration->$key;
			if (is_array($val) && count($val) === 1 && empty($val[0])) {
				$this->configuration->$key = [];
			}
		}

		if ((stripos((string)$this->configuration->ldapHost, 'ldaps://') === 0)
			&& $this->configuration->ldapTLS) {
			$this->configuration->ldapTLS = (string)false;
			$this->logger->info(
				'LDAPS (already using secure connection) and TLS do not work together. Switched off TLS.',
				['app' => 'user_ldap']
			);
		}
	}

	/**
	 * @throws ConfigurationIssueException
	 */
	private function doCriticalValidation(): void {
		//options that shall not be empty
		$options = ['ldapHost', 'ldapUserDisplayName',
			'ldapGroupDisplayName', 'ldapLoginFilter'];

		//ldapPort should not be empty either unless ldapHost is pointing to a socket
		if (!$this->configuration->usesLdapi()) {
			$options[] = 'ldapPort';
		}

		foreach ($options as $key) {
			$val = $this->configuration->$key;
			if (empty($val)) {
				switch ($key) {
					case 'ldapHost':
						$subj = 'LDAP Host';
						break;
					case 'ldapPort':
						$subj = 'LDAP Port';
						break;
					case 'ldapUserDisplayName':
						$subj = 'LDAP User Display Name';
						break;
					case 'ldapGroupDisplayName':
						$subj = 'LDAP Group Display Name';
						break;
					case 'ldapLoginFilter':
						$subj = 'LDAP Login Filter';
						break;
					default:
						$subj = $key;
						break;
				}
				throw new ConfigurationIssueException(
					'No ' . $subj . ' given!',
					$this->l10n->t('Mandatory field "%s" left empty', $subj),
				);
			}
		}

		//combinations
		$agent = $this->configuration->ldapAgentName;
		$pwd = $this->configuration->ldapAgentPassword;
		if ($agent === '' && $pwd !== '') {
			throw new ConfigurationIssueException(
				'A password is given, but not an LDAP agent',
				$this->l10n->t('A password is given, but not an LDAP agent'),
			);
		}
		if ($agent !== '' && $pwd === '') {
			throw new ConfigurationIssueException(
				'No password is given for the user agent',
				$this->l10n->t('No password is given for the user agent'),
			);
		}

		$base = $this->configuration->ldapBase;
		$baseUsers = $this->configuration->ldapBaseUsers;
		$baseGroups = $this->configuration->ldapBaseGroups;

		if (empty($base)) {
			throw new ConfigurationIssueException(
				'Not a single Base DN given',
				$this->l10n->t('No LDAP base DN was given'),
			);
		}

		if (!empty($baseUsers) && !$this->checkBasesAreValid($baseUsers, $base)) {
			throw new ConfigurationIssueException(
				'User base is not in root base',
				$this->l10n->t('User base DN is not a subnode of global base DN'),
			);
		}

		if (!empty($baseGroups) && !$this->checkBasesAreValid($baseGroups, $base)) {
			throw new ConfigurationIssueException(
				'Group base is not in root base',
				$this->l10n->t('Group base DN is not a subnode of global base DN'),
			);
		}

		if (mb_strpos((string)$this->configuration->ldapLoginFilter, '%uid', 0, 'UTF-8') === false) {
			throw new ConfigurationIssueException(
				'Login filter does not contain %uid placeholder.',
				$this->l10n->t('Login filter does not contain %s placeholder.', ['%uid']),
			);
		}
	}

	/**
	 * Checks that all bases are subnodes of one of the root bases
	 */
	private function checkBasesAreValid(array $bases, array $rootBases): bool {
		foreach ($bases as $base) {
			$ok = false;
			foreach ($rootBases as $rootBase) {
				if (str_ends_with($base, $rootBase)) {
					$ok = true;
					break;
				}
			}
			if (!$ok) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validates the user specified configuration
	 * @return bool true if configuration seems OK, false otherwise
	 */
	private function validateConfiguration(bool $throw = false): bool {
		if ($this->doNotValidate) {
			//don't do a validation if it is a new configuration with pure
			//default values. Will be allowed on changes via __set or
			//setConfiguration
			return false;
		}

		// first step: "soft" checks: settings that are not really
		// necessary, but advisable. If left empty, give an info message
		$this->doSoftValidation();

		//second step: critical checks. If left empty or filled wrong, mark as
		//not configured and give a warning.
		try {
			$this->doCriticalValidation();
			return true;
		} catch (ConfigurationIssueException $e) {
			if ($throw) {
				throw $e;
			}
			$this->logger->warning(
				'Configuration Error (prefix ' . $this->configPrefix . '): ' . $e->getMessage(),
				['exception' => $e]
			);
			return false;
		}
	}


	/**
	 * Connects and Binds to LDAP
	 *
	 * @throws ServerNotAvailableException
	 */
	private function establishConnection(): ?bool {
		if (!$this->configuration->ldapConfigurationActive) {
			return null;
		}
		static $phpLDAPinstalled = true;
		if (!$phpLDAPinstalled) {
			return false;
		}
		if (!$this->ignoreValidation && !$this->configured) {
			$this->logger->warning(
				'Configuration is invalid, cannot connect',
				['app' => 'user_ldap']
			);
			return false;
		}
		if (!$this->ldapConnectionRes) {
			if (!$this->ldap->areLDAPFunctionsAvailable()) {
				$phpLDAPinstalled = false;
				$this->logger->error(
					'function ldap_connect is not available. Make sure that the PHP ldap module is installed.',
					['app' => 'user_ldap']
				);

				return false;
			}

			$hasBackupHost = (trim($this->configuration->ldapBackupHost ?? '') !== '');
			$hasBackgroundHost = (trim($this->configuration->ldapBackgroundHost ?? '') !== '');
			$useBackgroundHost = (\OC::$CLI && $hasBackgroundHost);
			$overrideCacheKey = ($useBackgroundHost ? 'overrideBackgroundServer' : 'overrideMainServer');
			$forceBackupHost = ($this->configuration->ldapOverrideMainServer || $this->getFromCache($overrideCacheKey));
			$bindStatus = false;
			if (!$forceBackupHost) {
				try {
					$host = $this->configuration->ldapHost ?? '';
					$port = $this->configuration->ldapPort ?? '';
					if ($useBackgroundHost) {
						$host = $this->configuration->ldapBackgroundHost ?? '';
						$port = $this->configuration->ldapBackgroundPort ?? '';
					}
					$this->doConnect($host, $port);
					return $this->bind();
				} catch (ServerNotAvailableException $e) {
					if (!$hasBackupHost) {
						throw $e;
					}
				}
				$this->logger->warning(
					'Main LDAP not reachable, connecting to backup: {msg}',
					[
						'app' => 'user_ldap',
						'msg' => $e->getMessage(),
						'exception' => $e,
					]
				);
			}

			// if LDAP server is not reachable, try the Backup (Replica!) Server
			$this->doConnect($this->configuration->ldapBackupHost ?? '', $this->configuration->ldapBackupPort ?? '');
			$this->bindResult = [];
			$bindStatus = $this->bind();
			$error = $this->ldap->isResource($this->ldapConnectionRes) ?
				$this->ldap->errno($this->ldapConnectionRes) : -1;
			if ($bindStatus && $error === 0 && !$forceBackupHost) {
				//when bind to backup server succeeded and failed to main server,
				//skip contacting it for 15min
				$this->writeToCache($overrideCacheKey, true, 60 * 15);
			}

			return $bindStatus;
		}
		return null;
	}

	/**
	 * @param string $host
	 * @param string $port
	 * @throws \OC\ServerNotAvailableException
	 */
	private function doConnect($host, $port): bool {
		if ($host === '') {
			return false;
		}

		$this->ldapConnectionRes = $this->ldap->connect($host, $port) ?: null;

		if ($this->ldapConnectionRes === null) {
			throw new ServerNotAvailableException('Connection failed');
		}

		if (!$this->ldap->setOption($this->ldapConnectionRes, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			throw new ServerNotAvailableException('Could not set required LDAP Protocol version.');
		}

		if (!$this->ldap->setOption($this->ldapConnectionRes, LDAP_OPT_REFERRALS, 0)) {
			throw new ServerNotAvailableException('Could not disable LDAP referrals.');
		}

		if (!$this->ldap->setOption($this->ldapConnectionRes, LDAP_OPT_NETWORK_TIMEOUT, $this->configuration->ldapConnectionTimeout)) {
			throw new ServerNotAvailableException('Could not set network timeout');
		}

		if ($this->configuration->ldapTLS) {
			if ($this->configuration->turnOffCertCheck) {
				if ($this->ldap->setOption($this->ldapConnectionRes, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER)) {
					$this->logger->debug(
						'Turned off SSL certificate validation successfully.',
						['app' => 'user_ldap']
					);
				} else {
					$this->logger->warning(
						'Could not turn off SSL certificate validation.',
						['app' => 'user_ldap']
					);
				}
			}

			if (!$this->ldap->startTls($this->ldapConnectionRes)) {
				throw new ServerNotAvailableException('Start TLS failed, when connecting to LDAP host ' . $host . '.');
			}
		}

		return true;
	}

	/**
	 * Binds to LDAP
	 */
	public function bind() {
		if (!$this->configuration->ldapConfigurationActive) {
			return false;
		}
		$cr = $this->ldapConnectionRes;
		if (!$this->ldap->isResource($cr)) {
			$cr = $this->getConnectionResource();
		}

		if (
			count($this->bindResult) !== 0
			&& $this->bindResult['sum'] === md5($this->configuration->ldapAgentName . $this->configPrefix . $this->configuration->ldapAgentPassword)
		) {
			// don't attempt to bind again with the same data as before
			// bind might have been invoked via getConnectionResource(),
			// but we need results specifically for e.g. user login
			return $this->bindResult['result'];
		}

		$ldapLogin = @$this->ldap->bind($cr,
			$this->configuration->ldapAgentName,
			$this->configuration->ldapAgentPassword);

		$this->bindResult = [
			'sum' => md5($this->configuration->ldapAgentName . $this->configPrefix . $this->configuration->ldapAgentPassword),
			'result' => $ldapLogin,
		];

		if (!$ldapLogin) {
			$errno = $this->ldap->errno($cr);

			$this->logger->warning(
				'Bind failed: ' . $errno . ': ' . $this->ldap->error($cr),
				['app' => 'user_ldap']
			);

			// Set to failure mode, if LDAP error code is not one of
			// - LDAP_SUCCESS (0)
			// - LDAP_INVALID_CREDENTIALS (49)
			// - LDAP_INSUFFICIENT_ACCESS (50, spotted Apple Open Directory)
			// - LDAP_UNWILLING_TO_PERFORM (53, spotted eDirectory)
			if (!in_array($errno, [0, 49, 50, 53], true)) {
				$this->ldapConnectionRes = null;
			}

			return false;
		}
		return true;
	}
}
