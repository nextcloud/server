<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Lyonel Vincent <lyonel@ezix.org>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roger Szabo <roger.szabo@web.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP;

use OC\ServerNotAvailableException;

/**
 * magic properties (incomplete)
 * responsible for LDAP connections in context with the provided configuration
 *
 * @property string ldapHost
 * @property string ldapPort holds the port number
 * @property string ldapUserFilter
 * @property string ldapUserDisplayName
 * @property string ldapUserDisplayName2
 * @property boolean hasPagedResultSupport
 * @property string[] ldapBaseUsers
 * @property int|string ldapPagingSize holds an integer
 * @property bool|mixed|void ldapGroupMemberAssocAttr
 */
class Connection extends LDAPUtility {
	private $ldapConnectionRes = null;
	private $configPrefix;
	private $configID;
	private $configured = false;
	private $hasPagedResultSupport = true;
	//whether connection should be kept on __destruct
	private $dontDestruct = false;

	/**
	 * @var bool runtime flag that indicates whether supported primary groups are available
	 */
	public $hasPrimaryGroups = true;

	//cache handler
	protected $cache;

	/** @var Configuration settings handler **/
	protected $configuration;

	protected $doNotValidate = false;

	protected $ignoreValidation = false;

	/**
	 * Constructor
	 * @param ILDAPWrapper $ldap
	 * @param string $configPrefix a string with the prefix for the configkey column (appconfig table)
	 * @param string|null $configID a string with the value for the appid column (appconfig table) or null for on-the-fly connections
	 */
	public function __construct(ILDAPWrapper $ldap, $configPrefix = '', $configID = 'user_ldap') {
		parent::__construct($ldap);
		$this->configPrefix = $configPrefix;
		$this->configID = $configID;
		$this->configuration = new Configuration($configPrefix,
												 !is_null($configID));
		$memcache = \OC::$server->getMemCacheFactory();
		if($memcache->isAvailable()) {
			$this->cache = $memcache->create();
		}
		$helper = new Helper();
		$this->doNotValidate = !in_array($this->configPrefix,
			$helper->getServerConfigurationPrefixes());
		$this->hasPagedResultSupport =
			intval($this->configuration->ldapPagingSize) !== 0
			|| $this->ldap->hasPagedResultSupport();
	}

	public function __destruct() {
		if(!$this->dontDestruct && $this->ldap->isResource($this->ldapConnectionRes)) {
			@$this->ldap->unbind($this->ldapConnectionRes);
		};
	}

	/**
	 * defines behaviour when the instance is cloned
	 */
	public function __clone() {
		$this->configuration = new Configuration($this->configPrefix,
												 !is_null($this->configID));
		$this->ldapConnectionRes = null;
		$this->dontDestruct = true;
	}

	/**
	 * @param string $name
	 * @return bool|mixed|void
	 */
	public function __get($name) {
		if(!$this->configured) {
			$this->readConfiguration();
		}

		if($name === 'hasPagedResultSupport') {
			return $this->hasPagedResultSupport;
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
		if($before !== $after) {
			if(!empty($this->configID)) {
				$this->configuration->saveConfiguration();
			}
			$this->validateConfiguration();
		}
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
	 * Returns the LDAP handler
	 */
	public function getConnectionResource() {
		if(!$this->ldapConnectionRes) {
			$this->init();
		} else if(!$this->ldap->isResource($this->ldapConnectionRes)) {
			$this->ldapConnectionRes = null;
			$this->establishConnection();
		}
		if(is_null($this->ldapConnectionRes)) {
			\OCP\Util::writeLog('user_ldap', 'No LDAP Connection to server ' . $this->configuration->ldapHost, \OCP\Util::ERROR);
			throw new ServerNotAvailableException('Connection to LDAP server could not be established');
		}
		return $this->ldapConnectionRes;
	}

	/**
	 * resets the connection resource
	 */
	public function resetConnectionResource() {
		if(!is_null($this->ldapConnectionRes)) {
			@$this->ldap->unbind($this->ldapConnectionRes);
			$this->ldapConnectionRes = null;
		}
	}

	/**
	 * @param string|null $key
	 * @return string
	 */
	private function getCacheKey($key) {
		$prefix = 'LDAP-'.$this->configID.'-'.$this->configPrefix.'-';
		if(is_null($key)) {
			return $prefix;
		}
		return $prefix.md5($key);
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getFromCache($key) {
		if(!$this->configured) {
			$this->readConfiguration();
		}
		if(is_null($this->cache) || !$this->configuration->ldapCacheTTL) {
			return null;
		}
		$key = $this->getCacheKey($key);

		return json_decode(base64_decode($this->cache->get($key)), true);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function writeToCache($key, $value) {
		if(!$this->configured) {
			$this->readConfiguration();
		}
		if(is_null($this->cache)
			|| !$this->configuration->ldapCacheTTL
			|| !$this->configuration->ldapConfigurationActive) {
			return null;
		}
		$key   = $this->getCacheKey($key);
		$value = base64_encode(json_encode($value));
		$this->cache->set($key, $value, $this->configuration->ldapCacheTTL);
	}

	public function clearCache() {
		if(!is_null($this->cache)) {
			$this->cache->clear($this->getCacheKey(null));
		}
	}

	/**
	 * Caches the general LDAP configuration.
	 * @param bool $force optional. true, if the re-read should be forced. defaults
	 * to false.
	 * @return null
	 */
	private function readConfiguration($force = false) {
		if((!$this->configured || $force) && !is_null($this->configID)) {
			$this->configuration->readConfiguration();
			$this->configured = $this->validateConfiguration();
		}
	}

	/**
	 * set LDAP configuration with values delivered by an array, not read from configuration
	 * @param array $config array that holds the config parameters in an associated array
	 * @param array &$setParameters optional; array where the set fields will be given to
	 * @return boolean true if config validates, false otherwise. Check with $setParameters for detailed success on single parameters
	 */
	public function setConfiguration($config, &$setParameters = null) {
		if(is_null($setParameters)) {
			$setParameters = array();
		}
		$this->doNotValidate = false;
		$this->configuration->setConfiguration($config, $setParameters);
		if(count($setParameters) > 0) {
			$this->configured = $this->validateConfiguration();
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
		$result = array();
		foreach($cta as $dbkey => $configkey) {
			switch($configkey) {
				case 'homeFolderNamingRule':
					if(strpos($config[$configkey], 'attr:') === 0) {
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
					if(is_array($config[$configkey])) {
						$result[$dbkey] = implode("\n", $config[$configkey]);
						break;
					} //else follows default
				default:
					$result[$dbkey] = $config[$configkey];
			}
		}
		return $result;
	}

	private function doSoftValidation() {
		//if User or Group Base are not set, take over Base DN setting
		foreach(array('ldapBaseUsers', 'ldapBaseGroups') as $keyBase) {
			$val = $this->configuration->$keyBase;
			if(empty($val)) {
				$obj = strpos('Users', $keyBase) !== false ? 'Users' : 'Groups';
				\OCP\Util::writeLog('user_ldap',
									'Base tree for '.$obj.
									' is empty, using Base DN',
									\OCP\Util::INFO);
				$this->configuration->$keyBase = $this->configuration->ldapBase;
			}
		}

		foreach(array('ldapExpertUUIDUserAttr'  => 'ldapUuidUserAttribute',
					  'ldapExpertUUIDGroupAttr' => 'ldapUuidGroupAttribute')
				as $expertSetting => $effectiveSetting) {
			$uuidOverride = $this->configuration->$expertSetting;
			if(!empty($uuidOverride)) {
				$this->configuration->$effectiveSetting = $uuidOverride;
			} else {
				$uuidAttributes = array('auto', 'entryuuid', 'nsuniqueid',
										'objectguid', 'guid');
				if(!in_array($this->configuration->$effectiveSetting,
							$uuidAttributes)
					&& (!is_null($this->configID))) {
					$this->configuration->$effectiveSetting = 'auto';
					$this->configuration->saveConfiguration();
					\OCP\Util::writeLog('user_ldap',
										'Illegal value for the '.
										$effectiveSetting.', '.'reset to '.
										'autodetect.', \OCP\Util::INFO);
				}

			}
		}

		$backupPort = $this->configuration->ldapBackupPort;
		if(empty($backupPort)) {
			$this->configuration->backupPort = $this->configuration->ldapPort;
		}

		//make sure empty search attributes are saved as simple, empty array
		$saKeys = array('ldapAttributesForUserSearch',
						'ldapAttributesForGroupSearch');
		foreach($saKeys as $key) {
			$val = $this->configuration->$key;
			if(is_array($val) && count($val) === 1 && empty($val[0])) {
				$this->configuration->$key = array();
			}
		}

		if((stripos($this->configuration->ldapHost, 'ldaps://') === 0)
			&& $this->configuration->ldapTLS) {
			$this->configuration->ldapTLS = false;
			\OCP\Util::writeLog('user_ldap',
								'LDAPS (already using secure connection) and '.
								'TLS do not work together. Switched off TLS.',
								\OCP\Util::INFO);
		}
	}

	/**
	 * @return bool
	 */
	private function doCriticalValidation() {
		$configurationOK = true;
		$errorStr = 'Configuration Error (prefix '.
					strval($this->configPrefix).'): ';

		//options that shall not be empty
		$options = array('ldapHost', 'ldapPort', 'ldapUserDisplayName',
						 'ldapGroupDisplayName', 'ldapLoginFilter');
		foreach($options as $key) {
			$val = $this->configuration->$key;
			if(empty($val)) {
				switch($key) {
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
				$configurationOK = false;
				\OCP\Util::writeLog('user_ldap',
									$errorStr.'No '.$subj.' given!',
									\OCP\Util::WARN);
			}
		}

		//combinations
		$agent = $this->configuration->ldapAgentName;
		$pwd = $this->configuration->ldapAgentPassword;
		if((empty($agent) && !empty($pwd)) || (!empty($agent) && empty($pwd))) {
			\OCP\Util::writeLog('user_ldap',
								$errorStr.'either no password is given for the'.
								'user agent or a password is given, but not an'.
								'LDAP agent.',
				\OCP\Util::WARN);
			$configurationOK = false;
		}

		$base = $this->configuration->ldapBase;
		$baseUsers = $this->configuration->ldapBaseUsers;
		$baseGroups = $this->configuration->ldapBaseGroups;

		if(empty($base) && empty($baseUsers) && empty($baseGroups)) {
			\OCP\Util::writeLog('user_ldap',
								$errorStr.'Not a single Base DN given.',
								\OCP\Util::WARN);
			$configurationOK = false;
		}

		if(mb_strpos($this->configuration->ldapLoginFilter, '%uid', 0, 'UTF-8')
		   === false) {
			\OCP\Util::writeLog('user_ldap',
								$errorStr.'login filter does not contain %uid '.
								'place holder.',
								\OCP\Util::WARN);
			$configurationOK = false;
		}

		return $configurationOK;
	}

	/**
	 * Validates the user specified configuration
	 * @return bool true if configuration seems OK, false otherwise
	 */
	private function validateConfiguration() {

		if($this->doNotValidate) {
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
		return $this->doCriticalValidation();
	}


	/**
	 * Connects and Binds to LDAP
	 */
	private function establishConnection() {
		if(!$this->configuration->ldapConfigurationActive) {
			return null;
		}
		static $phpLDAPinstalled = true;
		if(!$phpLDAPinstalled) {
			return false;
		}
		if(!$this->ignoreValidation && !$this->configured) {
			\OCP\Util::writeLog('user_ldap',
								'Configuration is invalid, cannot connect',
								\OCP\Util::WARN);
			return false;
		}
		if(!$this->ldapConnectionRes) {
			if(!$this->ldap->areLDAPFunctionsAvailable()) {
				$phpLDAPinstalled = false;
				\OCP\Util::writeLog('user_ldap',
									'function ldap_connect is not available. Make '.
									'sure that the PHP ldap module is installed.',
									\OCP\Util::ERROR);

				return false;
			}
			if($this->configuration->turnOffCertCheck) {
				if(putenv('LDAPTLS_REQCERT=never')) {
					\OCP\Util::writeLog('user_ldap',
						'Turned off SSL certificate validation successfully.',
						\OCP\Util::DEBUG);
				} else {
					\OCP\Util::writeLog('user_ldap',
										'Could not turn off SSL certificate validation.',
										\OCP\Util::WARN);
				}
			}

			$bindStatus = false;
			$error = -1;
			try {
				if (!$this->configuration->ldapOverrideMainServer
					&& !$this->getFromCache('overrideMainServer')
				) {
					$this->doConnect($this->configuration->ldapHost,
						$this->configuration->ldapPort);
					$bindStatus = $this->bind();
					$error = $this->ldap->isResource($this->ldapConnectionRes) ?
						$this->ldap->errno($this->ldapConnectionRes) : -1;
				}
				if($bindStatus === true) {
					return $bindStatus;
				}
			} catch (\OC\ServerNotAvailableException $e) {
				if(trim($this->configuration->ldapBackupHost) === "") {
					throw $e;
				}
			}

			//if LDAP server is not reachable, try the Backup (Replica!) Server
			if(    $error !== 0
				|| $this->configuration->ldapOverrideMainServer
				|| $this->getFromCache('overrideMainServer'))
			{
				$this->doConnect($this->configuration->ldapBackupHost,
								 $this->configuration->ldapBackupPort);
				$bindStatus = $this->bind();
				if($bindStatus && $error === -1 && !$this->getFromCache('overrideMainServer')) {
					//when bind to backup server succeeded and failed to main server,
					//skip contacting him until next cache refresh
					$this->writeToCache('overrideMainServer', true);
				}
			}
			return $bindStatus;
		}
	}

	/**
	 * @param string $host
	 * @param string $port
	 * @return false|void
	 * @throws \OC\ServerNotAvailableException
	 */
	private function doConnect($host, $port) {
		if(empty($host)) {
			return false;
		}
		$this->ldapConnectionRes = $this->ldap->connect($host, $port);
		if($this->ldap->setOption($this->ldapConnectionRes, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			if($this->ldap->setOption($this->ldapConnectionRes, LDAP_OPT_REFERRALS, 0)) {
				if($this->configuration->ldapTLS) {
					$this->ldap->startTls($this->ldapConnectionRes);
				}
			}
		} else {
			throw new \OC\ServerNotAvailableException('Could not set required LDAP Protocol version.');
		}
	}

	/**
	 * Binds to LDAP
	 */
	public function bind() {
		static $getConnectionResourceAttempt = false;
		if(!$this->configuration->ldapConfigurationActive) {
			return false;
		}
		if($getConnectionResourceAttempt) {
			$getConnectionResourceAttempt = false;
			return false;
		}
		$getConnectionResourceAttempt = true;
		$cr = $this->getConnectionResource();
		$getConnectionResourceAttempt = false;
		if(!$this->ldap->isResource($cr)) {
			return false;
		}
		$ldapLogin = @$this->ldap->bind($cr,
										$this->configuration->ldapAgentName,
										$this->configuration->ldapAgentPassword);
		if(!$ldapLogin) {
			\OCP\Util::writeLog('user_ldap',
				'Bind failed: ' . $this->ldap->errno($cr) . ': ' . $this->ldap->error($cr),
				\OCP\Util::WARN);
			$this->ldapConnectionRes = null;
			return false;
		}
		return true;
	}

}
