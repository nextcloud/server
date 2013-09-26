<?php

/**
 * ownCloud – LDAP Connection
 *
 * @author Arthur Schiwon
 * @copyright 2012, 2013 Arthur Schiwon blizzz@owncloud.com
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

class Configuration {

	protected $configPrefix = null;
	protected $configRead = false;

	//settings
	protected $config = array(
		'ldapHost' => null,
		'ldapPort' => null,
		'ldapBackupHost' => null,
		'ldapBackupPort' => null,
		'ldapBase' => null,
		'ldapBaseUsers' => null,
		'ldapBaseGroups' => null,
		'ldapAgentName' => null,
		'ldapAgentPassword' => null,
		'ldapTLS' => null,
		'ldapNoCase' => null,
		'turnOffCertCheck' => null,
		'ldapIgnoreNamingRules' => null,
		'ldapUserDisplayName' => null,
		'ldapUserFilter' => null,
		'ldapGroupFilter' => null,
		'ldapGroupDisplayName' => null,
		'ldapGroupMemberAssocAttr' => null,
		'ldapLoginFilter' => null,
		'ldapQuotaAttribute' => null,
		'ldapQuotaDefault' => null,
		'ldapEmailAttribute' => null,
		'ldapCacheTTL' => null,
		'ldapUuidAttribute' => 'auto',
		'ldapOverrideUuidAttribute' => null,
		'ldapOverrideMainServer' => false,
		'ldapConfigurationActive' => false,
		'ldapAttributesForUserSearch' => null,
		'ldapAttributesForGroupSearch' => null,
		'homeFolderNamingRule' => null,
		'hasPagedResultSupport' => false,
		'ldapExpertUsernameAttr' => null,
		'ldapExpertUUIDAttr' => null,
	);

	public function __construct($configPrefix, $autoread = true) {
		$this->configPrefix = $configPrefix;
		if($autoread) {
			$this->readConfiguration();
		}
	}

	public function __get($name) {
		if(isset($this->config[$name])) {
			return $this->config[$name];
		}
	}

	public function __set($name, $value) {
		$this->setConfiguration(array($name => $value));
	}

	public function getConfiguration() {
		return $this->config;
	}

	/**
	 * @brief set LDAP configuration with values delivered by an array, not read
	 * from configuration. It does not save the configuration! To do so, you
	 * must call saveConfiguration afterwards.
	 * @param $config array that holds the config parameters in an associated
	 * array
	 * @param &$applied optional; array where the set fields will be given to
	 * @return null
	 */
	public function setConfiguration($config, &$applied = null) {
		if(!is_array($config)) {
			return false;
		}

		$cta = $this->getConfigTranslationArray();
		foreach($config as $inputkey => $val) {
			if(strpos($inputkey, '_') !== false && isset($cta[$inputkey])) {
				$key = $cta[$inputkey];
			} elseif(isset($this->config[$inputkey])) {
				$key = $inputkey;
			} else {
				continue;
			}

			$setMethod = 'setValue';
			switch($key) {
				case 'homeFolderNamingRule':
					if(!empty($val) && strpos($val, 'attr:') === false) {
						$val = 'attr:'.$val;
					}
				case 'ldapBase':
				case 'ldapBaseUsers':
				case 'ldapBaseGroups':
				case 'ldapAttributesForUserSearch':
				case 'ldapAttributesForGroupSearch':
					$setMethod = 'setMultiLine';
				default:
					$this->$setMethod($key, $val);
					if(is_array($applied)) {
						$applied[] = $inputkey;
					}
			}
		}

	}

	public function readConfiguration() {
		if(!$this->configRead && !is_null($this->configPrefix)) {
			$cta = array_flip($this->getConfigTranslationArray());
			foreach($this->config as $key => $val) {
// 				if($this->configPrefix == 's04') var_dump($key);
				if(!isset($cta[$key])) {
					//some are determined
					continue;
				}
				$dbkey = $cta[$key];
// 				if($this->configPrefix == 's04') var_dump($dbkey);
				switch($key) {
					case 'ldapBase':
					case 'ldapBaseUsers':
					case 'ldapBaseGroups':
					case 'ldapAttributesForUserSearch':
					case 'ldapAttributesForGroupSearch':
						$readMethod = 'getMultiLine';
						break;
					case 'ldapIgnoreNamingRules':
						$readMethod = 'getSystemValue';
						$dbkey = $key;
						break;
					case 'ldapAgentPassword':
						$readMethod = 'getPwd';
						break;
					case 'ldapUserDisplayName':
					case 'ldapGroupDisplayName':
						$readMethod = 'getLcValue';
						break;
					default:
						$readMethod = 'getValue';
						break;
				}
// 				if($this->configPrefix == 's04') var_dump($readMethod);
				$this->config[$key] = $this->$readMethod($dbkey);
			}
			$this->configRead = true;
		}
		if($this->configPrefix == 's03') {
// 			var_dump($this->config);

// 			die;
		}
	}

	/**
	 * @brief saves the current Configuration in the database
	 */
	public function saveConfiguration() {
		$cta = array_flip($this->getConfigTranslationArray());
		foreach($this->config as $key => $value) {
			switch ($key) {
				case 'ldapAgentPassword':
					$value = base64_encode($value);
					break;
				case 'ldapBase':
				case 'ldapBaseUsers':
				case 'ldapBaseGroups':
				case 'ldapAttributesForUserSearch':
				case 'ldapAttributesForGroupSearch':
					if(is_array($value)) {
						$value = implode("\n", $value);
					}
					break;
				//following options are not stored but detected, skip them
				case 'ldapIgnoreNamingRules':
				case 'ldapOverrideUuidAttribute':
				case 'hasPagedResultSupport':
					continue 2;
			}
			if(is_null($value)) {
				$value = '';
			}
			$this->saveValue($cta[$key], $value);
		}
	}

	protected function getMultiLine($varname) {
		$value = $this->getValue($varname);
		if(empty($value)) {
			$value = '';
		} else {
			$value = preg_split('/\r\n|\r|\n/', $value);
		}

		return $value;
	}

	protected function setMultiLine($varname, $value) {
		if(empty($value)) {
			$value = '';
		} else {
			$value = preg_split('/\r\n|\r|\n/', $value);
		}

		$this->setValue($varname, $value);
	}

	protected function getPwd($varname) {
		return base64_decode($this->getValue($varname));
	}

	protected function getLcValue($varname) {
		return mb_strtolower($this->getValue($varname), 'UTF-8');
	}

	protected function getSystemValue($varname) {
		//FIXME: if another system value is added, softcode the default value
		return \OCP\Config::getSystemValue($varname, false);
	}

	protected function getValue($varname) {
		static $defaults;
		if(is_null($defaults)) {
			$defaults = $this->getDefaults();
		}
// 		if($this->configPrefix == 's04') var_dump($this->configPrefix.$varname);
// 		if(0 == $this->configKeyToDBKey($varname)) {
// 			var_dump($varname);
// 			print("<pre>");
// 			debug_print_backtrace(); die;
// 		}
		return \OCP\Config::getAppValue('user_ldap',
										$this->configPrefix.$varname,
										$defaults[$varname]);
	}

	protected function setValue($varname, $value) {
		$this->config[$varname] = $value;
	}

	protected function saveValue($varname, $value) {
		return \OCP\Config::setAppValue('user_ldap',
										$this->configPrefix.$varname,
										$value);
	}

	/**
	 * @returns an associative array with the default values. Keys are correspond
	 * to config-value entries in the database table
	 */
	public function getDefaults() {
		return array(
			'ldap_host'							=> '',
			'ldap_port'							=> '389',
			'ldap_backup_host'					=> '',
			'ldap_backup_port'					=> '',
			'ldap_override_main_server'			=> '',
			'ldap_dn'							=> '',
			'ldap_agent_password'				=> '',
			'ldap_base'							=> '',
			'ldap_base_users'					=> '',
			'ldap_base_groups'					=> '',
			'ldap_userlist_filter'				=> 'objectClass=person',
			'ldap_login_filter'					=> 'uid=%uid',
			'ldap_group_filter'					=> 'objectClass=posixGroup',
			'ldap_display_name'					=> 'cn',
			'ldap_group_display_name'			=> 'cn',
			'ldap_tls'							=> 1,
			'ldap_nocase'						=> 0,
			'ldap_quota_def'					=> '',
			'ldap_quota_attr'					=> '',
			'ldap_email_attr'					=> '',
			'ldap_group_member_assoc_attribute'	=> 'uniqueMember',
			'ldap_cache_ttl'					=> 600,
			'ldap_uuid_attribute'				=> 'auto',
			'ldap_override_uuid_attribute'		=> 0,
			'home_folder_naming_rule'			=> '',
			'ldap_turn_off_cert_check'			=> 0,
			'ldap_configuration_active'			=> 1,
			'ldap_attributes_for_user_search'	=> '',
			'ldap_attributes_for_group_search'	=> '',
			'ldap_expert_username_attr'			=> '',
			'ldap_expert_uuid_attr'				=> '',
		);
	}

	/**
	 * @return returns an array that maps internal variable names to database fields
	 */
	public function getConfigTranslationArray() {
		//TODO: merge them into one representation
		static $array = array(
			'ldap_host'							=> 'ldapHost',
			'ldap_port'							=> 'ldapPort',
			'ldap_backup_host'					=> 'ldapBackupHost',
			'ldap_backup_port'					=> 'ldapBackupPort',
			'ldap_override_main_server' 		=> 'ldapOverrideMainServer',
			'ldap_dn'							=> 'ldapAgentName',
			'ldap_agent_password'				=> 'ldapAgentPassword',
			'ldap_base'							=> 'ldapBase',
			'ldap_base_users'					=> 'ldapBaseUsers',
			'ldap_base_groups'					=> 'ldapBaseGroups',
			'ldap_userlist_filter'				=> 'ldapUserFilter',
			'ldap_login_filter'					=> 'ldapLoginFilter',
			'ldap_group_filter'					=> 'ldapGroupFilter',
			'ldap_display_name'					=> 'ldapUserDisplayName',
			'ldap_group_display_name'			=> 'ldapGroupDisplayName',
			'ldap_tls'							=> 'ldapTLS',
			'ldap_nocase'						=> 'ldapNoCase',
			'ldap_quota_def'					=> 'ldapQuotaDefault',
			'ldap_quota_attr'					=> 'ldapQuotaAttribute',
			'ldap_email_attr'					=> 'ldapEmailAttribute',
			'ldap_group_member_assoc_attribute'	=> 'ldapGroupMemberAssocAttr',
			'ldap_cache_ttl'					=> 'ldapCacheTTL',
			'home_folder_naming_rule' 			=> 'homeFolderNamingRule',
			'ldap_turn_off_cert_check' 			=> 'turnOffCertCheck',
			'ldap_configuration_active' 		=> 'ldapConfigurationActive',
			'ldap_attributes_for_user_search' 	=> 'ldapAttributesForUserSearch',
			'ldap_attributes_for_group_search'	=> 'ldapAttributesForGroupSearch',
			'ldap_expert_username_attr' 		=> 'ldapExpertUsernameAttr',
			'ldap_expert_uuid_attr' 			=> 'ldapExpertUUIDAttr',
		);
		return $array;
	}

}