
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc This class represents the view belonging to the advanced tab
	 * in the LDAP wizard.
	 */
	const WizardTabAdvanced = OCA.LDAP.Wizard.WizardTabGeneric.subClass({
		/**
		 * initializes the instance. Always call it after initialization.
		 *
		 * @param tabIndex
		 * @param tabID
		 */
		init(tabIndex, tabID) {
			this._super(tabIndex, tabID)

			const items = {
				// Connection settings
				ldap_configuration_active: {
					$element: $('#ldap_configuration_active'),
					setMethod: 'setConfigurationState',
				},
				ldap_backup_host: {
					$element: $('#ldap_backup_host'),
					setMethod: 'setBackupHost',
				},
				ldap_backup_port: {
					$element: $('#ldap_backup_port'),
					setMethod: 'setBackupPort',
				},
				ldap_override_main_server: {
					$element: $('#ldap_override_main_server'),
					setMethod: 'setOverrideMainServerState',
				},
				ldap_turn_off_cert_check: {
					$element: $('#ldap_turn_off_cert_check'),
					setMethod: 'setCertCheckDisabled',
				},
				ldap_cache_ttl: {
					$element: $('#ldap_cache_ttl'),
					setMethod: 'setCacheTTL',
				},

				// Directory Settings
				ldap_display_name: {
					$element: $('#ldap_display_name'),
					setMethod: 'setUserDisplayName',
				},
				ldap_user_display_name_2: {
					$element: $('#ldap_user_display_name_2'),
					setMethod: 'setUserDisplayName2',
				},
				ldap_base_users: {
					$element: $('#ldap_base_users'),
					setMethod: 'setBaseDNUsers',
				},
				ldap_attributes_for_user_search: {
					$element: $('#ldap_attributes_for_user_search'),
					setMethod: 'setSearchAttributesUsers',
				},
				ldap_group_display_name: {
					$element: $('#ldap_group_display_name'),
					setMethod: 'setGroupDisplayName',
				},
				ldap_base_groups: {
					$element: $('#ldap_base_groups'),
					setMethod: 'setBaseDNGroups',
				},
				ldap_attributes_for_group_search: {
					$element: $('#ldap_attributes_for_group_search'),
					setMethod: 'setSearchAttributesGroups',
				},
				ldap_group_member_assoc_attribute: {
					$element: $('#ldap_group_member_assoc_attribute'),
					setMethod: 'setGroupMemberAssociationAttribute',
				},
				ldap_dynamic_group_member_url: {
					$element: $('#ldap_dynamic_group_member_url'),
					setMethod: 'setDynamicGroupMemberURL',
				},
				ldap_nested_groups: {
					$element: $('#ldap_nested_groups'),
					setMethod: 'setUseNestedGroups',
				},
				ldap_paging_size: {
					$element: $('#ldap_paging_size'),
					setMethod: 'setPagingSize',
				},
				ldap_turn_on_pwd_change: {
					$element: $('#ldap_turn_on_pwd_change'),
					setMethod: 'setPasswordChangeEnabled',
				},
				ldap_default_ppolicy_dn: {
					$element: $('#ldap_default_ppolicy_dn'),
					setMethod: 'setDefaultPPolicyDN',
				},

				// Special Attributes
				ldap_quota_attr: {
					$element: $('#ldap_quota_attr'),
					setMethod: 'setQuotaAttribute',
				},
				ldap_quota_def: {
					$element: $('#ldap_quota_def'),
					setMethod: 'setQuotaDefault',
				},
				ldap_email_attr: {
					$element: $('#ldap_email_attr'),
					setMethod: 'setEmailAttribute',
				},
				home_folder_naming_rule: {
					$element: $('#home_folder_naming_rule'),
					setMethod: 'setHomeFolderAttribute',
				},
				ldap_ext_storage_home_attribute: {
					$element: $('#ldap_ext_storage_home_attribute'),
					setMethod: 'setExternalStorageHomeAttribute',
				},
			}
			this.setManagedItems(items)
		},

		/**
		 * Sets the config model for this view and subscribes to some events.
		 * Also binds the config chooser to the model
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} configModel
		 */
		setModel(configModel) {
			this._super(configModel)
			this.configModel.on('configLoaded', this.onConfigLoaded, this)
			this.configModel.on('receivedLdapFeature', this.onResultReceived, this)
		},

		/**
		 * updates the experienced admin check box
		 *
		 * @param {string} isConfigActive contains an int
		 */
		setConfigurationState(isConfigActive) {
			this.setElementValue(
				this.managedItems.ldap_configuration_active.$element, isConfigActive
			)
		},

		/**
		 * updates the backup host configuration text field
		 *
		 * @param {string} host
		 */
		setBackupHost(host) {
			this.setElementValue(this.managedItems.ldap_backup_host.$element, host)
		},

		/**
		 * updates the backup port configuration text field
		 *
		 * @param {string} port
		 */
		setBackupPort(port) {
			this.setElementValue(this.managedItems.ldap_backup_port.$element, port)
		},

		/**
		 * sets whether the main server should be overridden or not
		 *
		 * @param {string} doOverride contains an int
		 */
		setOverrideMainServerState(doOverride) {
			this.setElementValue(
				this.managedItems.ldap_override_main_server.$element, doOverride
			)
		},

		/**
		 * sets whether the SSL/TLS certification check shout be disabled
		 *
		 * @param {string} doCertCheck contains an int
		 */
		setCertCheckDisabled(doCertCheck) {
			this.setElementValue(
				this.managedItems.ldap_turn_off_cert_check.$element, doCertCheck
			)
		},

		/**
		 * sets the time-to-live of the LDAP cache (in seconds)
		 *
		 * @param {string} cacheTTL contains an int
		 */
		setCacheTTL(cacheTTL) {
			this.setElementValue(this.managedItems.ldap_cache_ttl.$element, cacheTTL)
		},

		/**
		 * sets the user display name attribute
		 *
		 * @param {string} attribute
		 */
		setUserDisplayName(attribute) {
			this.setElementValue(this.managedItems.ldap_display_name.$element, attribute)
		},

		/**
		 * sets the additional user display name attribute
		 *
		 * @param {string} attribute
		 */
		setUserDisplayName2(attribute) {
			this.setElementValue(this.managedItems.ldap_user_display_name_2.$element, attribute)
		},

		/**
		 * sets the Base DN for users
		 *
		 * @param {string} base
		 */
		setBaseDNUsers(base) {
			this.setElementValue(this.managedItems.ldap_base_users.$element, base)
		},

		/**
		 * sets the attributes for user searches
		 *
		 * @param {string} attributes
		 */
		setSearchAttributesUsers(attributes) {
			this.setElementValue(this.managedItems.ldap_attributes_for_user_search.$element, attributes)
		},

		/**
		 * sets the display name attribute for groups
		 *
		 * @param {string} attribute
		 */
		setGroupDisplayName(attribute) {
			this.setElementValue(this.managedItems.ldap_group_display_name.$element, attribute)
		},

		/**
		 * sets the Base DN for groups
		 *
		 * @param {string} base
		 */
		setBaseDNGroups(base) {
			this.setElementValue(this.managedItems.ldap_base_groups.$element, base)
		},

		/**
		 * sets the attributes for group search
		 *
		 * @param {string} attributes
		 */
		setSearchAttributesGroups(attributes) {
			this.setElementValue(this.managedItems.ldap_attributes_for_group_search.$element, attributes)
		},

		/**
		 * sets the attribute for the association of users and groups
		 *
		 * @param {string} attribute
		 */
		setGroupMemberAssociationAttribute(attribute) {
			this.setElementValue(this.managedItems.ldap_group_member_assoc_attribute.$element, attribute)
		},

		/**
		  * sets the dynamic group member url attribute
		  *
		  * @param {string} attribute
		  */
		setDynamicGroupMemberURL(attribute) {
			this.setElementValue(this.managedItems.ldap_dynamic_group_member_url.$element, attribute)
		},

		/**
		 * enabled or disables the use of nested groups (groups in groups in
		 * groups…)
		 *
		 * @param {string} useNestedGroups contains an int
		 */
		setUseNestedGroups(useNestedGroups) {
			this.setElementValue(this.managedItems.ldap_nested_groups.$element, useNestedGroups)
		},

		/**
		 * sets the size of pages for paged search
		 *
		 * @param {string} size contains an int
		 */
		setPagingSize(size) {
			this.setElementValue(this.managedItems.ldap_paging_size.$element, size)
		},

		/**
		 * sets whether the password changes per user should be enabled
		 *
		 * @param {string} doPasswordChange contains an int
		 */
		setPasswordChangeEnabled(doPasswordChange) {
			this.setElementValue(
				this.managedItems.ldap_turn_on_pwd_change.$element, doPasswordChange
			)
		},

		/**
		  * sets the default ppolicy attribute
		  *
		  * @param {string} attribute
		  */
		setDefaultPPolicyDN(attribute) {
			this.setElementValue(this.managedItems.ldap_default_ppolicy_dn.$element, attribute)
		},

		/**
		 * sets the email attribute
		 *
		 * @param {string} attribute
		 */
		setEmailAttribute(attribute) {
			this.setElementValue(this.managedItems.ldap_email_attr.$element, attribute)
		},

		/**
		 * sets the external storage home attribute
		 *
		 * @param {string} attribute
		 */
		setExternalStorageHomeAttribute(attribute) {
			this.setElementValue(this.managedItems.ldap_ext_storage_home_attribute.$element, attribute)
		},

		/**
		 * sets the quota attribute
		 *
		 * @param {string} attribute
		 */
		setQuotaAttribute(attribute) {
			this.setElementValue(this.managedItems.ldap_quota_attr.$element, attribute)
		},

		/**
		 * sets the default quota for LDAP users
		 *
		 * @param {string} quota contains an int
		 */
		setQuotaDefault(quota) {
			this.setElementValue(this.managedItems.ldap_quota_def.$element, quota)
		},

		/**
		 * sets the attribute for the Nextcloud user specific home folder location
		 *
		 * @param {string} attribute
		 */
		setHomeFolderAttribute(attribute) {
			this.setElementValue(this.managedItems.home_folder_naming_rule.$element, attribute)
		},

		/**
		 * deals with the result of the Test Connection test
		 *
		 * @param {WizardTabAdvanced} view
		 * @param {FeaturePayload} payload
		 */
		onResultReceived(view, payload) {
			if (payload.feature === 'TestConfiguration') {
				OC.Notification.showTemporary(payload.data.message)
			}
		},
	})

	OCA.LDAP.Wizard.WizardTabAdvanced = WizardTabAdvanced
})()
