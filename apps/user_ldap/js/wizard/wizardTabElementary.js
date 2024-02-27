
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc This class represents the view belonging to the server tab
	 * in the LDAP wizard.
	 */
	var WizardTabElementary = OCA.LDAP.Wizard.WizardTabGeneric.subClass({
		/** @property {number} */
		_configChooserNextServerNumber: 1,

		baseDNTestTriggered: false,

		/**
		 * initializes the instance. Always call it after initialization.
		 *
		 * @param {any} tabIndex -
		 * @param {any} tabID -
		 */
		init: function (tabIndex, tabID) {
			tabIndex = 0;
			this._super(tabIndex, tabID);
			this.isActive = true;
			this.$configChooser = $('#ldap_serverconfig_chooser');

			var items = {
				ldap_host: {
					$element: $('#ldap_host'),
					setMethod: 'setHost'
				},
				ldap_port: {
					$element: $('#ldap_port'),
					setMethod: 'setPort',
					$relatedElements: $('.ldapDetectPort')
				},
				ldap_dn: {
					$element: $('#ldap_dn'),
					setMethod: 'setAgentDN',
					preventAutoSave: true,
					$saveButton: $('.ldapSaveAgentCredentials')
				},
				ldap_agent_password: {
					$element: $('#ldap_agent_password'),
					setMethod: 'setAgentPwd',
					preventAutoSave: true,
					$saveButton: $('.ldapSaveAgentCredentials')
				},
				ldap_base: {
					$element: $('#ldap_base'),
					setMethod: 'setBase',
					$relatedElements: $('.ldapDetectBase, .ldapTestBase'),
					$detectButton: $('.ldapDetectBase'),
					$testButton: $('.ldapTestBase')
				},
				ldap_base_test: {
					$element: $('#ldap_base')
				},
				ldap_experienced_admin: {
					$element: $('#ldap_experienced_admin'),
					setMethod: 'setExperiencedAdmin'
				}
			};
			this.setManagedItems(items);
			_.bindAll(this,
				'onPortButtonClick',
				'onBaseDNButtonClick',
				'onBaseDNTestButtonClick'
			);
			this.managedItems.ldap_port.$relatedElements.click(this.onPortButtonClick);
			this.managedItems.ldap_base.$detectButton.click(this.onBaseDNButtonClick);
			this.managedItems.ldap_base.$testButton.click(this.onBaseDNTestButtonClick);
		},

		/**
		 * Sets the config model for this view and subscribes to some events.
		 * Also binds the config chooser to the model
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} configModel
		 */
		setModel: function(configModel) {
			this._super(configModel);
			this.configModel.on('configLoaded', this.onConfigSwitch, this);
			this.configModel.on('newConfiguration', this.onNewConfiguration, this);
			this.configModel.on('deleteConfiguration', this.onDeleteConfiguration, this);
			this.configModel.on('receivedLdapFeature', this.onTestResultReceived, this);
			this._enableConfigChooser();
			this._enableConfigButtons();
		},

		/**
		 * returns the currently selected configuration ID
		 *
		 * @returns {string}
		 */
		getConfigID: function() {
			return this.$configChooser.val();
		},

		/**
		 * updates the host configuration text field
		 *
		 * @param {string} host
		 */
		setHost: function(host) {
			this.setElementValue(this.managedItems.ldap_host.$element, host);
			if(host) {
				this.enableElement(this.managedItems.ldap_port.$relatedElements);
			} else {
				this.disableElement(this.managedItems.ldap_port.$relatedElements);
			}
		},

		/**
		 * updates the port configuration text field
		 *
		 * @param {string} port
		 */
		setPort: function(port) {
			this.setElementValue(this.managedItems.ldap_port.$element, port);
		},

		/**
		 * updates the user (agent) DN text field
		 *
		 * @param {string} agentDN
		 */
		setAgentDN: function(agentDN) {
			this.setElementValue(this.managedItems.ldap_dn.$element, agentDN);
		},

		/**
		 * updates the user (agent) password field
		 *
		 * @param {string} agentPwd
		 */
		setAgentPwd: function(agentPwd) {
			this.setElementValue(
				this.managedItems.ldap_agent_password.$element, agentPwd
			);
			if (agentPwd && $('html').hasClass('lte9')) {
				// make it a password field again (IE fix, placeholders bug)
				this.managedItems.ldap_agent_password.$element.attr('type', 'password');
			}
		},
		/**
		 * updates the base DN text area
		 *
		 * @param {string} bases
		 */
		setBase: function(bases) {
			this.setElementValue(this.managedItems.ldap_base.$element, bases);
			if(!bases) {
				this.disableElement(this.managedItems.ldap_base.$testButton);
			} else {
				this.enableElement(this.managedItems.ldap_base.$testButton);
			}
		},

		/**
		 * updates the experienced admin check box
		 *
		 * @param {string} xpAdminMode contains an int
		 */
		setExperiencedAdmin: function(xpAdminMode) {
			this.setElementValue(
				this.managedItems.ldap_experienced_admin.$element, xpAdminMode
			);
		},

		/**
		 * @inheritdoc
		 */
		overrideErrorMessage: function(message, key) {
			var original = message;
			message = this._super(message, key);
			if(original !== message) {
				// we pass the parents change
				return message;
			}
			switch(key) {
				case 'ldap_port':
					if (message === 'Invalid credentials') {
						return t('user_ldap', 'Please check the credentials, they seem to be wrong.');
					} else {
						return t('user_ldap', 'Please specify the port, it could not be auto-detected.');
					}
					break;
				case 'ldap_base':
					if(   message === 'Server is unwilling to perform'
						|| message === 'Could not connect to LDAP'
					) {
						return t('user_ldap', 'Base DN could not be auto-detected, please revise credentials, host and port.');
					}
					return t('user_ldap', 'Could not detect Base DN, please enter it manually.');
					break;
			}
			return message;
		},

		/**
		 * resets the view when a configuration switch happened.
		 *
		 * @param {WizardTabElementary} view
		 * @param {Object} configuration
		 */
		onConfigSwitch: function(view, configuration) {
			this.baseDNTestTriggered = false;
			view.disableElement(view.managedItems.ldap_port.$relatedElements);
			view.managedItems.ldap_dn.$saveButton.removeClass('primary');
			view.onConfigLoaded(view, configuration);
		},

		/**
		 * updates the configuration chooser when a new configuration was added
		 * which also means it is being switched to. The configuration fields
		 * are updated on a different step.
		 *
		 * @param {WizardTabElementary} view
		 * @param {Object} result
		 */
		onNewConfiguration: function(view, result) {
			if(result.isSuccess === true) {
				var nthServer = view._configChooserNextServerNumber;
				view.$configChooser.find('option:selected').removeAttr('selected');
				var html = '<option value="'+result.configPrefix+'" selected="selected">'+t('user_ldap','{nthServer}. Server', {nthServer: nthServer})+'</option>';
				if(view.$configChooser.find('option:last').length > 0) {
					view.$configChooser.find('option:last').after(html);
				} else {
					view.$configChooser.html(html);
				}

				view._configChooserNextServerNumber++;
			}
		},

		/**
		 * updates the configuration chooser upon the deletion of a
		 * configuration and, if necessary, loads an existing one.
		 *
		 * @param {any} view -
		 * @param {any} result -
		 */
		onDeleteConfiguration: function(view, result) {
			if(result.isSuccess === true) {
				if(view.getConfigID() === result.configPrefix) {
					// if the deleted value is still the selected one (99% of
					// the cases), remove it from the list and load the topmost
					view.$configChooser.find('option:selected').remove();
					view.$configChooser.find('option:first').select();
					if(view.$configChooser.find(' option').length < 1) {
						view.configModel.newConfig(false);
					} else {
						view.configModel.load(view.getConfigID());
					}
				} else {
					// otherwise just remove the entry
					view.$configChooser.find('option[value=' + result.configPrefix + ']').remove();
				}
			} else {
				OC.Notification.showTemporary(result.errorMessage);
			}
		},

		/**
		 * Base DN test results will arrive here
		 *
		 * @param {WizardTabElementary} view
		 * @param {FeaturePayload} payload
		 */
		onTestResultReceived: function(view, payload) {
			if(view.baseDNTestTriggered && payload.feature === 'TestBaseDN') {
				view.enableElement(view.managedItems.ldap_base.$testButton);
				var message;
				if(payload.data.status === 'success') {
					var objectsFound = parseInt(payload.data.changes.ldap_test_base, 10);
					if(objectsFound < 1) {
						message = t('user_ldap', 'No object found in the given Base DN. Please revise.');
					} else if(objectsFound > 1000) {
						message = t('user_ldap', 'More than 1,000 directory entries available.');
					} else {
						message = n(
							'user_ldap',
							'{objectsFound} entry available within the provided Base DN',
							'{objectsFound} entries available within the provided Base DN',
							objectsFound,
							{
							objectsFound: objectsFound
							});
					}
				} else {
					message = view.overrideErrorMessage(payload.data.message);
					message = message || t('user_ldap', 'An error occurred. Please check the Base DN, as well as connection settings and credentials.');
					if(payload.data.message) {
						console.warn(payload.data.message);
					}
				}
				OC.Notification.showTemporary(message);
			}
		},

		/**
		 * request to count the users with the current filter
		 *
		 * @param {Event} event
		 */
		onPortButtonClick: function(event) {
			event.preventDefault();
			this.configModel.requestWizard('ldap_port');
		},

		/**
		 * request to count the users with the current filter
		 *
		 * @param {Event} event
		 */
		onBaseDNButtonClick: function(event) {
			event.preventDefault();
			this.configModel.requestWizard('ldap_base');
		},

		/**
		 * request to count the users with the current filter
		 *
		 * @param {Event} event
		 */
		onBaseDNTestButtonClick: function(event) {
			event.preventDefault();
			this.baseDNTestTriggered = true;
			this.configModel.requestWizard('ldap_test_base');
			this.disableElement(this.managedItems.ldap_base.$testButton);
		},

		/**
		 * registers the change event on the configuration chooser and makes
		 * the model load a newly selected configuration
		 *
		 * @private
		 */
		_enableConfigChooser: function() {
			this._configChooserNextServerNumber = this.$configChooser.find(' option').length + 1;
			var view = this;
			this.$configChooser.change(function(){
				var value = view.$configChooser.find(' option:selected:first').attr('value');
				view.configModel.load(value);
			});
		},

		/**
		 * adds actions to the action buttons for configuration management
		 *
		 * @private
		 */
		_enableConfigButtons: function() {
			var view = this;
			$('#ldap_action_delete_configuration').click(function(event) {
				event.preventDefault();
				OC.dialogs.confirm(
					t('user_ldap', 'Do you really want to delete the current Server Configuration?'),
					t('user_ldap', 'Confirm Deletion'),
					function(doDelete) {
						if(doDelete) {
							view.configModel.deleteConfig(view.getConfigID());
						}
					},
					false
				);
			});

			$('#ldap_action_add_configuration').click(function(event) {
				event.preventDefault();
				view.configModel.newConfig(false);
			});

			$('#ldap_action_copy_configuration').click(function(event) {
				event.preventDefault();
				view.configModel.newConfig(true);
			});
		}
	});

	OCA.LDAP.Wizard.WizardTabElementary = WizardTabElementary;
})();
