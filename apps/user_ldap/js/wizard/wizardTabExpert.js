
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc This class represents the view belonging to the expert tab
	 * in the LDAP wizard.
	 */
	var WizardTabExpert = OCA.LDAP.Wizard.WizardTabGeneric.subClass({
		/**
		 * initializes the instance. Always call it after initialization.
		 *
		 * @param {any} tabIndex -
		 * @param {any} tabID -
		 */
		init: function (tabIndex, tabID) {
			this._super(tabIndex, tabID);

			var items = {
				ldap_expert_username_attr: {
					$element: $('#ldap_expert_username_attr'),
					setMethod: 'setUsernameAttribute'
				},
				ldap_expert_uuid_user_attr: {
					$element: $('#ldap_expert_uuid_user_attr'),
					setMethod: 'setUserUUIDAttribute'
				},
				ldap_expert_uuid_group_attr: {
					$element: $('#ldap_expert_uuid_group_attr'),
					setMethod: 'setGroupUUIDAttribute'
				},

				//Buttons
				ldap_action_clear_user_mappings: {
					$element: $('#ldap_action_clear_user_mappings')
				},
				ldap_action_clear_group_mappings: {
					$element: $('#ldap_action_clear_group_mappings')
				}

			};
			this.setManagedItems(items);
			_.bindAll(this, 'onClearUserMappingsClick', 'onClearGroupMappingsClick');
			this.managedItems.ldap_action_clear_user_mappings.$element.click(this.onClearUserMappingsClick);
			this.managedItems.ldap_action_clear_group_mappings.$element.click(this.onClearGroupMappingsClick);
		},

		/**
		 * Sets the config model for this view and subscribes to some events.
		 * Also binds the config chooser to the model
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} configModel
		 */
		setModel: function(configModel) {
			this._super(configModel);
			this.configModel.on('configLoaded', this.onConfigLoaded, this);
			this.configModel.on('receivedLdapFeature', this.onResultReceived, this);
		},

		/**
		 * sets the attribute to be used to create an Nextcloud ID (username)
		 *
		 * @param {string} attribute
		 */
		setUsernameAttribute: function(attribute) {
			this.setElementValue(this.managedItems.ldap_expert_username_attr.$element, attribute);
		},

		/**
		 * sets the attribute that provides an unique identifier per LDAP user
		 * entry
		 *
		 * @param {string} attribute
		 */
		setUserUUIDAttribute: function(attribute) {
			this.setElementValue(this.managedItems.ldap_expert_uuid_user_attr.$element, attribute);
		},

		/**
		 * sets the attribute that provides an unique identifier per LDAP group
		 * entry
		 *
		 * @param {string} attribute
		 */
		setGroupUUIDAttribute: function(attribute) {
			this.setElementValue(this.managedItems.ldap_expert_uuid_group_attr.$element, attribute);
		},

		/**
		 * requests clearing of all user mappings
		 */
		onClearUserMappingsClick: function() {
			this.configModel.requestWizard('ldap_action_clear_user_mappings', {ldap_clear_mapping: 'user'});
		},

		/**
		 * requests clearing of all group mappings
		 */
		onClearGroupMappingsClick: function() {
			this.configModel.requestWizard('ldap_action_clear_group_mappings', {ldap_clear_mapping: 'group'});
		},

		/**
		 * deals with the result of the Test Connection test
		 *
		 * @param {WizardTabAdvanced} view
		 * @param {FeaturePayload} payload
		 */
		onResultReceived: function(view, payload) {
			if(payload.feature === 'ClearMappings') {
				var message;
				if(payload.data.status === 'success') {
					message = t('user_ldap', 'Mappings cleared successfully!');
				} else {
					message = t('user_ldap', 'Error while clearing the mappings.');
				}
				OC.Notification.showTemporary(message);
			}
		}
	});

	OCA.LDAP.Wizard.WizardTabExpert = WizardTabExpert;
})();
