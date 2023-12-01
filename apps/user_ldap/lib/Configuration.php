<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Alexander Bergolth <leo@strike.wu.ac.at>
 * @author Alex Weirig <alex.weirig@technolink.lu>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lennart Rosam <hello@takuto.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Marc Hefter <marchefter@march42.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Xuanwo <xuanwo@yunify.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\User_LDAP;

/**
 * @property int ldapPagingSize holds an integer
 * @property string ldapUserAvatarRule
 */
class Configuration {
	public const AVATAR_PREFIX_DEFAULT = 'default';
	public const AVATAR_PREFIX_NONE = 'none';
	public const AVATAR_PREFIX_DATA_ATTRIBUTE = 'data:';

	public const LDAP_SERVER_FEATURE_UNKNOWN = 'unknown';
	public const LDAP_SERVER_FEATURE_AVAILABLE = 'available';
	public const LDAP_SERVER_FEATURE_UNAVAILABLE = 'unavailable';

	/**
	 * @var string
	 */
	protected $configPrefix;
	/**
	 * @var bool
	 */
	protected $configRead = false;
	/**
	 * @var string[]
	 */
	protected array $unsavedChanges = [];

	/**
	 * @var array<string, mixed> settings
	 */
	protected $config = [
		'ldapHost' => null,
		'ldapPort' => null,
		'ldapBackupHost' => null,
		'ldapBackupPort' => null,
		'ldapBackgroundHost' => null,
		'ldapBackgroundPort' => null,
		'ldapBase' => null,
		'ldapBaseUsers' => null,
		'ldapBaseGroups' => null,
		'ldapAgentName' => null,
		'ldapAgentPassword' => null,
		'ldapTLS' => null,
		'turnOffCertCheck' => null,
		'ldapIgnoreNamingRules' => null,
		'ldapUserDisplayName' => null,
		'ldapUserDisplayName2' => null,
		'ldapUserAvatarRule' => null,
		'ldapGidNumber' => null,
		'ldapUserFilterObjectclass' => null,
		'ldapUserFilterGroups' => null,
		'ldapUserFilter' => null,
		'ldapUserFilterMode' => null,
		'ldapGroupFilter' => null,
		'ldapGroupFilterMode' => null,
		'ldapGroupFilterObjectclass' => null,
		'ldapGroupFilterGroups' => null,
		'ldapGroupDisplayName' => null,
		'ldapGroupMemberAssocAttr' => null,
		'ldapLoginFilter' => null,
		'ldapLoginFilterMode' => null,
		'ldapLoginFilterEmail' => null,
		'ldapLoginFilterUsername' => null,
		'ldapLoginFilterAttributes' => null,
		'ldapQuotaAttribute' => null,
		'ldapQuotaDefault' => null,
		'ldapEmailAttribute' => null,
		'ldapCacheTTL' => null,
		'ldapUuidUserAttribute' => 'auto',
		'ldapUuidGroupAttribute' => 'auto',
		'ldapOverrideMainServer' => false,
		'ldapConfigurationActive' => false,
		'ldapAttributesForUserSearch' => null,
		'ldapAttributesForGroupSearch' => null,
		'ldapExperiencedAdmin' => false,
		'homeFolderNamingRule' => null,
		'hasMemberOfFilterSupport' => false,
		'useMemberOfToDetectMembership' => true,
		'ldapExpertUsernameAttr' => null,
		'ldapExpertUUIDUserAttr' => null,
		'ldapExpertUUIDGroupAttr' => null,
		'markRemnantsAsDisabled' => false,
		'lastJpegPhotoLookup' => null,
		'ldapNestedGroups' => false,
		'ldapPagingSize' => null,
		'turnOnPasswordChange' => false,
		'ldapDynamicGroupMemberURL' => null,
		'ldapDefaultPPolicyDN' => null,
		'ldapExtStorageHomeAttribute' => null,
		'ldapMatchingRuleInChainState' => self::LDAP_SERVER_FEATURE_UNKNOWN,
		'ldapConnectionTimeout' => 15,
		'ldapAttributePhone' => null,
		'ldapAttributeWebsite' => null,
		'ldapAttributeAddress' => null,
		'ldapAttributeTwitter' => null,
		'ldapAttributeFediverse' => null,
		'ldapAttributeOrganisation' => null,
		'ldapAttributeRole' => null,
		'ldapAttributeHeadline' => null,
		'ldapAttributeBiography' => null,
		'ldapAdminGroup' => '',
	];

	public function __construct(string $configPrefix, bool $autoRead = true) {
		$this->configPrefix = $configPrefix;
		if ($autoRead) {
			$this->readConfiguration();
		}
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function __get($name) {
		if (isset($this->config[$name])) {
			return $this->config[$name];
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$this->setConfiguration([$name => $value]);
	}

	public function getConfiguration(): array {
		return $this->config;
	}

	/**
	 * set LDAP configuration with values delivered by an array, not read
	 * from configuration. It does not save the configuration! To do so, you
	 * must call saveConfiguration afterwards.
	 * @param array $config array that holds the config parameters in an associated
	 * array
	 * @param array &$applied optional; array where the set fields will be given to
	 */
	public function setConfiguration(array $config, array &$applied = null): void {
		$cta = $this->getConfigTranslationArray();
		foreach ($config as $inputKey => $val) {
			if (str_contains($inputKey, '_') && array_key_exists($inputKey, $cta)) {
				$key = $cta[$inputKey];
			} elseif (array_key_exists($inputKey, $this->config)) {
				$key = $inputKey;
			} else {
				continue;
			}

			$setMethod = 'setValue';
			switch ($key) {
				case 'ldapAgentPassword':
					$setMethod = 'setRawValue';
					break;
				case 'homeFolderNamingRule':
					$trimmedVal = trim($val);
					if ($trimmedVal !== '' && !str_contains($val, 'attr:')) {
						$val = 'attr:'.$trimmedVal;
					}
					break;
				case 'ldapBase':
				case 'ldapBaseUsers':
				case 'ldapBaseGroups':
				case 'ldapAttributesForUserSearch':
				case 'ldapAttributesForGroupSearch':
				case 'ldapUserFilterObjectclass':
				case 'ldapUserFilterGroups':
				case 'ldapGroupFilterObjectclass':
				case 'ldapGroupFilterGroups':
				case 'ldapLoginFilterAttributes':
					$setMethod = 'setMultiLine';
					break;
			}
			$this->$setMethod($key, $val);
			if (is_array($applied)) {
				$applied[] = $inputKey;
				// storing key as index avoids duplication, and as value for simplicity
			}
			$this->unsavedChanges[$key] = $key;
		}
	}

	public function readConfiguration(): void {
		if (!$this->configRead) {
			$cta = array_flip($this->getConfigTranslationArray());
			foreach ($this->config as $key => $val) {
				if (!isset($cta[$key])) {
					//some are determined
					continue;
				}
				$dbKey = $cta[$key];
				switch ($key) {
					case 'ldapBase':
					case 'ldapBaseUsers':
					case 'ldapBaseGroups':
					case 'ldapAttributesForUserSearch':
					case 'ldapAttributesForGroupSearch':
					case 'ldapUserFilterObjectclass':
					case 'ldapUserFilterGroups':
					case 'ldapGroupFilterObjectclass':
					case 'ldapGroupFilterGroups':
					case 'ldapLoginFilterAttributes':
						$readMethod = 'getMultiLine';
						break;
					case 'ldapIgnoreNamingRules':
						$readMethod = 'getSystemValue';
						$dbKey = $key;
						break;
					case 'ldapAgentPassword':
						$readMethod = 'getPwd';
						break;
					case 'ldapUserDisplayName2':
					case 'ldapGroupDisplayName':
						$readMethod = 'getLcValue';
						break;
					case 'ldapUserDisplayName':
					default:
						// user display name does not lower case because
						// we rely on an upper case N as indicator whether to
						// auto-detect it or not. FIXME
						$readMethod = 'getValue';
						break;
				}
				$this->config[$key] = $this->$readMethod($dbKey);
			}
			$this->configRead = true;
		}
	}

	/**
	 * saves the current config changes in the database
	 */
	public function saveConfiguration(): void {
		$cta = array_flip($this->getConfigTranslationArray());
		$changed = false;
		foreach ($this->unsavedChanges as $key) {
			$value = $this->config[$key];
			switch ($key) {
				case 'ldapAgentPassword':
					$value = base64_encode($value);
					break;
				case 'ldapBase':
				case 'ldapBaseUsers':
				case 'ldapBaseGroups':
				case 'ldapAttributesForUserSearch':
				case 'ldapAttributesForGroupSearch':
				case 'ldapUserFilterObjectclass':
				case 'ldapUserFilterGroups':
				case 'ldapGroupFilterObjectclass':
				case 'ldapGroupFilterGroups':
				case 'ldapLoginFilterAttributes':
					if (is_array($value)) {
						$value = implode("\n", $value);
					}
					break;
					//following options are not stored but detected, skip them
				case 'ldapIgnoreNamingRules':
				case 'ldapUuidUserAttribute':
				case 'ldapUuidGroupAttribute':
					continue 2;
			}
			if (is_null($value)) {
				$value = '';
			}
			$changed = true;
			$this->saveValue($cta[$key], $value);
		}
		if ($changed) {
			$this->saveValue('_lastChange', (string)time());
		}
		$this->unsavedChanges = [];
	}

	/**
	 * @param string $varName
	 * @return array|string
	 */
	protected function getMultiLine($varName) {
		$value = $this->getValue($varName);
		if (empty($value)) {
			$value = '';
		} else {
			$value = preg_split('/\r\n|\r|\n/', $value);
		}

		return $value;
	}

	/**
	 * Sets multi-line values as arrays
	 *
	 * @param string $varName name of config-key
	 * @param array|string $value to set
	 */
	protected function setMultiLine(string $varName, $value): void {
		if (empty($value)) {
			$value = '';
		} elseif (!is_array($value)) {
			$value = preg_split('/\r\n|\r|\n|;/', $value);
			if ($value === false) {
				$value = '';
			}
		}

		if (!is_array($value)) {
			$finalValue = trim($value);
		} else {
			$finalValue = [];
			foreach ($value as $key => $val) {
				if (is_string($val)) {
					$val = trim($val);
					if ($val !== '') {
						//accidental line breaks are not wanted and can cause
						// odd behaviour. Thus, away with them.
						$finalValue[] = $val;
					}
				} else {
					$finalValue[] = $val;
				}
			}
		}

		$this->setRawValue($varName, $finalValue);
	}

	protected function getPwd(string $varName): string {
		return base64_decode($this->getValue($varName));
	}

	protected function getLcValue(string $varName): string {
		return mb_strtolower($this->getValue($varName), 'UTF-8');
	}

	protected function getSystemValue(string $varName): string {
		//FIXME: if another system value is added, softcode the default value
		return \OC::$server->getConfig()->getSystemValue($varName, false);
	}

	protected function getValue(string $varName): string {
		static $defaults;
		if (is_null($defaults)) {
			$defaults = $this->getDefaults();
		}
		return \OC::$server->getConfig()->getAppValue('user_ldap',
			$this->configPrefix.$varName,
			$defaults[$varName]);
	}

	/**
	 * Sets a scalar value.
	 *
	 * @param string $varName name of config key
	 * @param mixed $value to set
	 */
	protected function setValue(string $varName, $value): void {
		if (is_string($value)) {
			$value = trim($value);
		}
		$this->config[$varName] = $value;
	}

	/**
	 * Sets a scalar value without trimming.
	 *
	 * @param string $varName name of config key
	 * @param mixed $value to set
	 */
	protected function setRawValue(string $varName, $value): void {
		$this->config[$varName] = $value;
	}

	protected function saveValue(string $varName, string $value): bool {
		\OC::$server->getConfig()->setAppValue(
			'user_ldap',
			$this->configPrefix.$varName,
			$value
		);
		return true;
	}

	/**
	 * @return array an associative array with the default values. Keys are correspond
	 * to config-value entries in the database table
	 */
	public function getDefaults(): array {
		return [
			'ldap_host' => '',
			'ldap_port' => '',
			'ldap_backup_host' => '',
			'ldap_backup_port' => '',
			'ldap_background_host' => '',
			'ldap_background_port' => '',
			'ldap_override_main_server' => '',
			'ldap_dn' => '',
			'ldap_agent_password' => '',
			'ldap_base' => '',
			'ldap_base_users' => '',
			'ldap_base_groups' => '',
			'ldap_userlist_filter' => '',
			'ldap_user_filter_mode' => 0,
			'ldap_userfilter_objectclass' => '',
			'ldap_userfilter_groups' => '',
			'ldap_login_filter' => '',
			'ldap_login_filter_mode' => 0,
			'ldap_loginfilter_email' => 0,
			'ldap_loginfilter_username' => 1,
			'ldap_loginfilter_attributes' => '',
			'ldap_group_filter' => '',
			'ldap_group_filter_mode' => 0,
			'ldap_groupfilter_objectclass' => '',
			'ldap_groupfilter_groups' => '',
			'ldap_gid_number' => 'gidNumber',
			'ldap_display_name' => 'displayName',
			'ldap_user_display_name_2' => '',
			'ldap_group_display_name' => 'cn',
			'ldap_tls' => 0,
			'ldap_quota_def' => '',
			'ldap_quota_attr' => '',
			'ldap_email_attr' => '',
			'ldap_group_member_assoc_attribute' => '',
			'ldap_cache_ttl' => 600,
			'ldap_uuid_user_attribute' => 'auto',
			'ldap_uuid_group_attribute' => 'auto',
			'home_folder_naming_rule' => '',
			'ldap_turn_off_cert_check' => 0,
			'ldap_configuration_active' => 0,
			'ldap_attributes_for_user_search' => '',
			'ldap_attributes_for_group_search' => '',
			'ldap_expert_username_attr' => '',
			'ldap_expert_uuid_user_attr' => '',
			'ldap_expert_uuid_group_attr' => '',
			'has_memberof_filter_support' => 0,
			'use_memberof_to_detect_membership' => 1,
			'ldap_mark_remnants_as_disabled' => 0,
			'last_jpegPhoto_lookup' => 0,
			'ldap_nested_groups' => 0,
			'ldap_paging_size' => 500,
			'ldap_turn_on_pwd_change' => 0,
			'ldap_experienced_admin' => 0,
			'ldap_dynamic_group_member_url' => '',
			'ldap_default_ppolicy_dn' => '',
			'ldap_user_avatar_rule' => 'default',
			'ldap_ext_storage_home_attribute' => '',
			'ldap_matching_rule_in_chain_state' => self::LDAP_SERVER_FEATURE_UNKNOWN,
			'ldap_connection_timeout' => 15,
			'ldap_attr_phone' => '',
			'ldap_attr_website' => '',
			'ldap_attr_address' => '',
			'ldap_attr_twitter' => '',
			'ldap_attr_fediverse' => '',
			'ldap_attr_organisation' => '',
			'ldap_attr_role' => '',
			'ldap_attr_headline' => '',
			'ldap_attr_biography' => '',
			'ldap_admin_group' => '',
		];
	}

	/**
	 * @return array that maps internal variable names to database fields
	 */
	public function getConfigTranslationArray(): array {
		//TODO: merge them into one representation
		static $array = [
			'ldap_host' => 'ldapHost',
			'ldap_port' => 'ldapPort',
			'ldap_backup_host' => 'ldapBackupHost',
			'ldap_backup_port' => 'ldapBackupPort',
			'ldap_background_host' => 'ldapBackgroundHost',
			'ldap_background_port' => 'ldapBackgroundPort',
			'ldap_override_main_server' => 'ldapOverrideMainServer',
			'ldap_dn' => 'ldapAgentName',
			'ldap_agent_password' => 'ldapAgentPassword',
			'ldap_base' => 'ldapBase',
			'ldap_base_users' => 'ldapBaseUsers',
			'ldap_base_groups' => 'ldapBaseGroups',
			'ldap_userfilter_objectclass' => 'ldapUserFilterObjectclass',
			'ldap_userfilter_groups' => 'ldapUserFilterGroups',
			'ldap_userlist_filter' => 'ldapUserFilter',
			'ldap_user_filter_mode' => 'ldapUserFilterMode',
			'ldap_user_avatar_rule' => 'ldapUserAvatarRule',
			'ldap_login_filter' => 'ldapLoginFilter',
			'ldap_login_filter_mode' => 'ldapLoginFilterMode',
			'ldap_loginfilter_email' => 'ldapLoginFilterEmail',
			'ldap_loginfilter_username' => 'ldapLoginFilterUsername',
			'ldap_loginfilter_attributes' => 'ldapLoginFilterAttributes',
			'ldap_group_filter' => 'ldapGroupFilter',
			'ldap_group_filter_mode' => 'ldapGroupFilterMode',
			'ldap_groupfilter_objectclass' => 'ldapGroupFilterObjectclass',
			'ldap_groupfilter_groups' => 'ldapGroupFilterGroups',
			'ldap_gid_number' => 'ldapGidNumber',
			'ldap_display_name' => 'ldapUserDisplayName',
			'ldap_user_display_name_2' => 'ldapUserDisplayName2',
			'ldap_group_display_name' => 'ldapGroupDisplayName',
			'ldap_tls' => 'ldapTLS',
			'ldap_quota_def' => 'ldapQuotaDefault',
			'ldap_quota_attr' => 'ldapQuotaAttribute',
			'ldap_email_attr' => 'ldapEmailAttribute',
			'ldap_group_member_assoc_attribute' => 'ldapGroupMemberAssocAttr',
			'ldap_cache_ttl' => 'ldapCacheTTL',
			'home_folder_naming_rule' => 'homeFolderNamingRule',
			'ldap_turn_off_cert_check' => 'turnOffCertCheck',
			'ldap_configuration_active' => 'ldapConfigurationActive',
			'ldap_attributes_for_user_search' => 'ldapAttributesForUserSearch',
			'ldap_attributes_for_group_search' => 'ldapAttributesForGroupSearch',
			'ldap_expert_username_attr' => 'ldapExpertUsernameAttr',
			'ldap_expert_uuid_user_attr' => 'ldapExpertUUIDUserAttr',
			'ldap_expert_uuid_group_attr' => 'ldapExpertUUIDGroupAttr',
			'has_memberof_filter_support' => 'hasMemberOfFilterSupport',
			'use_memberof_to_detect_membership' => 'useMemberOfToDetectMembership',
			'ldap_mark_remnants_as_disabled' => 'markRemnantsAsDisabled',
			'last_jpegPhoto_lookup' => 'lastJpegPhotoLookup',
			'ldap_nested_groups' => 'ldapNestedGroups',
			'ldap_paging_size' => 'ldapPagingSize',
			'ldap_turn_on_pwd_change' => 'turnOnPasswordChange',
			'ldap_experienced_admin' => 'ldapExperiencedAdmin',
			'ldap_dynamic_group_member_url' => 'ldapDynamicGroupMemberURL',
			'ldap_default_ppolicy_dn' => 'ldapDefaultPPolicyDN',
			'ldap_ext_storage_home_attribute' => 'ldapExtStorageHomeAttribute',
			'ldap_matching_rule_in_chain_state' => 'ldapMatchingRuleInChainState',
			'ldapIgnoreNamingRules' => 'ldapIgnoreNamingRules',	// sysconfig
			'ldap_connection_timeout' => 'ldapConnectionTimeout',
			'ldap_attr_phone' => 'ldapAttributePhone',
			'ldap_attr_website' => 'ldapAttributeWebsite',
			'ldap_attr_address' => 'ldapAttributeAddress',
			'ldap_attr_twitter' => 'ldapAttributeTwitter',
			'ldap_attr_fediverse' => 'ldapAttributeFediverse',
			'ldap_attr_organisation' => 'ldapAttributeOrganisation',
			'ldap_attr_role' => 'ldapAttributeRole',
			'ldap_attr_headline' => 'ldapAttributeHeadline',
			'ldap_attr_biography' => 'ldapAttributeBiography',
			'ldap_admin_group' => 'ldapAdminGroup',
		];
		return $array;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function resolveRule(string $rule): array {
		if ($rule === 'avatar') {
			return $this->getAvatarAttributes();
		}
		throw new \RuntimeException('Invalid rule');
	}

	public function getAvatarAttributes(): array {
		$value = $this->ldapUserAvatarRule ?: self::AVATAR_PREFIX_DEFAULT;
		$defaultAttributes = ['jpegphoto', 'thumbnailphoto'];

		if ($value === self::AVATAR_PREFIX_NONE) {
			return [];
		}
		if (str_starts_with($value, self::AVATAR_PREFIX_DATA_ATTRIBUTE)) {
			$attribute = trim(substr($value, strlen(self::AVATAR_PREFIX_DATA_ATTRIBUTE)));
			if ($attribute === '') {
				return $defaultAttributes;
			}
			return [strtolower($attribute)];
		}
		if ($value !== self::AVATAR_PREFIX_DEFAULT) {
			\OC::$server->getLogger()->warning('Invalid config value to ldapUserAvatarRule; falling back to default.');
		}
		return $defaultAttributes;
	}

	/**
	 * Returns TRUE if the ldapHost variable starts with 'ldapi://'
	 */
	public function usesLdapi(): bool {
		return (substr($this->config['ldapHost'], 0, strlen('ldapi://')) === 'ldapi://');
	}
}
