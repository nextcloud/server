<?php

/**
 * ownCloud – LDAP Access
 *
 * @author Arthur Schiwon
 * @copyright 2012 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\user_ldap\lib;

class Connection {
	private $ldapConnectionRes = null;
	private $configID;
	private $configured = false;

	//cached settings
	protected $config = array(
		'ldapHost' => null,
		'ldapPort' => null,
		'ldapBase' => null,
		'ldapBaseUsers' => null,
		'ldapBaseGroups' => null,
		'ldapAgentName' => null,
		'ldapAgentPassword' => null,
		'ldapTLS' => null,
		'ldapNoCase' => null,
		'ldapIgnoreNamingRules' => null,
		'ldapUserDisplayName' => null,
		'ldapUserFilter' => null,
		'ldapGroupFilter' => null,
		'ldapGroupDisplayName' => null,
		'ldapLoginFilter' => null,
		'ldapQuotaAttribute' => null,
		'ldapQuotaDefault' => null,
		'ldapEmailAttribute' => null,
	);

	public function __construct($configID = 'user_ldap') {
		$this->configID = $configID;
	}

	public function __destruct() {
		@ldap_unbind($this->ldapConnectionRes);
	}

	public function __get($name) {
		if(!$this->configured) {
			$this->readConfiguration();
		}

		if(isset($this->config[$name])) {
			return $this->config[$name];
		}
	}

	/**
	 * @brief initializes the LDAP backend
	 * @param $force read the config settings no matter what
	 *
	 * initializes the LDAP backend
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
		}
		if(is_null($this->ldapConnectionRes)) {
			\OCP\Util::writeLog('user_ldap', 'Connection could not be established', \OCP\Util::ERROR);
		}
		return $this->ldapConnectionRes;
	}

	/**
	 * Caches the general LDAP configuration.
	 */
	private function readConfiguration($force = false) {
		\OCP\Util::writeLog('user_ldap','Checking conf state: isConfigured? '.print_r($this->configured, true).' isForce? '.print_r($force, true).' configID? '.print_r($this->configID, true), \OCP\Util::DEBUG);
		if((!$this->configured || $force) && !is_null($this->configID)) {
			\OCP\Util::writeLog('user_ldap','Reading the configuration', \OCP\Util::DEBUG);
			$this->config['ldapHost']              = \OCP\Config::getAppValue($this->configID, 'ldap_host', '');
			$this->config['ldapPort']              = \OCP\Config::getAppValue($this->configID, 'ldap_port', 389);
			$this->config['ldapAgentName']         = \OCP\Config::getAppValue($this->configID, 'ldap_dn','');
			$this->config['ldapAgentPassword']     = base64_decode(\OCP\Config::getAppValue($this->configID, 'ldap_agent_password',''));
			$this->config['ldapBase']              = \OCP\Config::getAppValue($this->configID, 'ldap_base', '');
			$this->config['ldapBaseUsers']         = \OCP\Config::getAppValue($this->configID, 'ldap_base_users',$this->config['ldapBase']);
			$this->config['ldapBaseGroups']        = \OCP\Config::getAppValue($this->configID, 'ldap_base_groups', $this->config['ldapBase']);
			$this->config['ldapTLS']               = \OCP\Config::getAppValue($this->configID, 'ldap_tls',0);
			$this->config['ldapNoCase']            = \OCP\Config::getAppValue($this->configID, 'ldap_nocase', 0);
			$this->config['ldapUserDisplayName']   = mb_strtolower(\OCP\Config::getAppValue($this->configID, 'ldap_display_name', 'uid'), 'UTF-8');
			$this->config['ldapUserFilter']        = \OCP\Config::getAppValue($this->configID, 'ldap_userlist_filter','objectClass=person');
			$this->config['ldapGroupFilter']        = \OCP\Config::getAppValue($this->configID, 'ldap_group_filter','(objectClass=posixGroup)');
			$this->config['ldapLoginFilter']       = \OCP\Config::getAppValue($this->configID, 'ldap_login_filter', '(uid=%uid)');
			$this->config['ldapGroupDisplayName']  = mb_strtolower(\OCP\Config::getAppValue($this->configID, 'ldap_group_display_name', 'uid'), 'UTF-8');
			$this->config['ldapQuotaAttribute']    = \OCP\Config::getAppValue($this->configID, 'ldap_quota_attr', '');
			$this->config['ldapQuotaDefault']      = \OCP\Config::getAppValue($this->configID, 'ldap_quota_def', '');
			$this->config['ldapEmailAttribute']      = \OCP\Config::getAppValue($this->configID, 'ldap_email_attr', '');
			$this->config['ldapGroupMemberAssocAttr']      = \OCP\Config::getAppValue($this->configID, 'ldap_group_member_assoc_attribute', 'uniqueMember');
			$this->config['ldapIgnoreNamingRules'] = \OCP\Config::getSystemValue('ldapIgnoreNamingRules', false);

			$this->configured = $this->validateConfiguration();
		}
	}

	/**
	 * @brief set LDAP configuration with values delivered by an array, not read from configuration
	 * @param $config array that holds the config parameters in an associated array
	 * @param &$setParameters optional; array where the set fields will be given to
	 * @return true if config validates, false otherwise. Check with $setParameters for detailed success on single parameters
	 */
	public function setConfiguration($config, &$setParameters = null) {
		if(!is_array($config)) {
			return false;
		}

		foreach($config as $parameter => $value) {
		    if(isset($this->config[$parameter])) {
				$this->config[$parameter] = $value;
				if(is_array($setParameters)) {
					$setParameters[] = $parameter;
				}
		    }
		}

		$this->configured = $this->validateConfiguration();

		return $this->configured;
	}

	/**
	 * @brief Validates the user specified configuration
	 * @returns true if configuration seems OK, false otherwise
	 */
	private function validateConfiguration() {
		//first step: "soft" checks: settings that are not really necessary, but advisable. If left empty, give an info message
		if(empty($this->config['ldapBaseUsers'])) {
			\OCP\Util::writeLog('user_ldap', 'Base tree for Users is empty, using Base DN', \OCP\Util::INFO);
			$this->config['ldapBaseUsers'] = $this->config['ldapBase'];
		}
		if(empty($this->config['ldapBaseGroups'])) {
			\OCP\Util::writeLog('user_ldap', 'Base tree for Groups is empty, using Base DN', \OCP\Util::INFO);
			$this->config['ldapBaseGroups'] = $this->config['ldapBase'];
		}
		if(empty($this->config['ldapGroupFilter']) && empty($this->config['ldapGroupMemberAssocAttr'])) {
			\OCP\Util::writeLog('user_ldap', 'No group filter is specified, LDAP group feature will not be used.', \OCP\Util::INFO);
		}

		//second step: critical checks. If left empty or filled wrong, set as unconfigured and give a warning.
		$configurationOK = true;
		if(empty($this->config['ldapHost'])) {
			\OCP\Util::writeLog('user_ldap', 'No LDAP host given, won`t connect.', \OCP\Util::WARN);
			$configurationOK = false;
		}
		if(empty($this->config['ldapPort'])) {
			\OCP\Util::writeLog('user_ldap', 'No LDAP Port given, won`t connect.', \OCP\Util::WARN);
			$configurationOK = false;
		}
		if((empty($this->config['ldapAgentName']) && !empty($this->config['ldapAgentPassword']))
			|| (!empty($this->config['ldapAgentName']) && empty($this->config['ldapAgentPassword']))) {
			\OCP\Util::writeLog('user_ldap', 'Either no password given for the user agent or a password is given, but no LDAP agent; won`t connect.', \OCP\Util::WARN);
			$configurationOK = false;
		}
		//TODO: check if ldapAgentName is in DN form
		if(empty($this->config['ldapBase']) && (empty($this->config['ldapBaseUsers']) && empty($this->config['ldapBaseGroups']))) {
			\OCP\Util::writeLog('user_ldap', 'No Base DN given, won`t connect.', \OCP\Util::WARN);
			$configurationOK = false;
		}
		if(empty($this->config['ldapUserDisplayName'])) {
			\OCP\Util::writeLog('user_ldap', 'No user display name attribute specified, won`t connect.', \OCP\Util::WARN);
			$configurationOK = false;
		}
		if(empty($this->config['ldapGroupDisplayName'])) {
			\OCP\Util::writeLog('user_ldap', 'No group display name attribute specified, won`t connect.', \OCP\Util::WARN);
			$configurationOK = false;
		}
		if(empty($this->config['ldapLoginFilter'])) {
			\OCP\Util::writeLog('user_ldap', 'No login filter specified, won`t connect.', \OCP\Util::WARN);
			$configurationOK = false;
		}
		if(mb_strpos($this->config['ldapLoginFilter'], '%uid', 0, 'UTF-8') === false) {
			\OCP\Util::writeLog('user_ldap', 'Login filter does not contain %uid place holder, won`t connect.', \OCP\Util::WARN);
			\OCP\Util::writeLog('user_ldap', 'Login filter was ' . $this->config['ldapLoginFilter'], \OCP\Util::DEBUG);
			$configurationOK = false;
		}

		return $configurationOK;
	}

	/**
	 * Connects and Binds to LDAP
	 */
	private function establishConnection() {
		static $phpLDAPinstalled = true;
		if(!$phpLDAPinstalled) {
			return false;
		}
		if(!$this->configured) {
			\OCP\Util::writeLog('user_ldap', 'Configuration is invalid, cannot connect', \OCP\Util::WARN);
			return false;
		}
		if(!$this->ldapConnectionRes) {
			if(!function_exists('ldap_connect')) {
				$phpLDAPinstalled = false;
				\OCP\Util::writeLog('user_ldap', 'function ldap_connect is not available. Make sure that the PHP ldap module is installed.', \OCP\Util::ERROR);

				return false;
			}
			$this->ldapConnectionRes = ldap_connect($this->config['ldapHost'], $this->config['ldapPort']);
			if(ldap_set_option($this->ldapConnectionRes, LDAP_OPT_PROTOCOL_VERSION, 3)) {
					if(ldap_set_option($this->ldapConnectionRes, LDAP_OPT_REFERRALS, 0)) {
						if($this->config['ldapTLS']) {
							ldap_start_tls($this->ldapConnectionRes);
						}
					}
			}

			return $this->bind();
		}
	}

	/**
	 * Binds to LDAP
	 */
	public function bind() {
		$ldapLogin = @ldap_bind($this->getConnectionResource(), $this->config['ldapAgentName'], $this->config['ldapAgentPassword']);
		if(!$ldapLogin) {
			\OCP\Util::writeLog('user_ldap', 'Bind failed: ' . ldap_errno($this->ldapConnectionRes) . ': ' . ldap_error($this->ldapConnectionRes), \OCP\Util::ERROR);
			$this->ldapConnectionRes = null;
			return false;
		}
		return true;
	}

}