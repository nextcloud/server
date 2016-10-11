/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc This class represents the view belonging to the login filter
	 * tab in the LDAP wizard.
	 */
	var WizardTabLoginFilter = OCA.LDAP.Wizard.WizardTabGeneric.subClass({
		/**
		 * initializes the instance. Always call it after initialization.
		 *
		 * @param tabIndex
		 * @param tabID
		 */
		init: function (tabIndex, tabID) {
			this._super(tabIndex, tabID);

			var items = {
				ldap_loginfilter_username: {
					$element: $('#ldap_loginfilter_username'),
					setMethod: 'setLoginAttributeUsername'
				},
				ldap_loginfilter_email: {
					$element: $('#ldap_loginfilter_email'),
					setMethod: 'setLoginAttributeEmail'
				},
				ldap_login_filter_mode: {
					setMethod: 'setFilterModeOnce'
				},
				ldap_loginfilter_attributes: {
					$element: $('#ldap_loginfilter_attributes'),
					setMethod: 'setLoginAttributesOther'
				},
				ldap_login_filter: {
					$element: $('#ldap_login_filter'),
					setMethod: 'setLoginFilter'
				},
				loginFilterRawToggle: {
					$element: $('#toggleRawLoginFilter')
				},
				loginFilterRawContainer: {
					$element: $('#rawLoginFilterContainer')
				},
				ldap_test_loginname: {
					$element: $('#ldap_test_loginname'),
					$relatedElements: $('.ldapVerifyLoginName')
				}
			};
			this.setManagedItems(items);

			this.filterModeKey = 'ldapLoginFilterMode';
			this._initMultiSelect(
				this.managedItems.ldap_loginfilter_attributes.$element,
				t('user_ldap', 'Select attributes')
			);
			this.filterName = 'ldap_login_filter';
			this._initFilterModeSwitcher(
				this.managedItems.loginFilterRawToggle.$element,
				this.managedItems.loginFilterRawContainer.$element,
				[
					this.managedItems.ldap_loginfilter_username.$element,
					this.managedItems.ldap_loginfilter_email.$element,
					this.managedItems.ldap_loginfilter_attributes.$element
				],
				'ldap_login_filter_mode'
			);
			_.bindAll(this, 'onVerifyClick', 'onTestLoginnameChange');
			this.managedItems.ldap_test_loginname.$element.keyup(this.onTestLoginnameChange);
			this.managedItems.ldap_test_loginname.$relatedElements.click(this.onVerifyClick);
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
			this.configModel.on('receivedLdapFeature', this.onFeatureReceived, this);
		},

		/**
		 * sets the selected attributes
		 *
		 * @param {Array} attributes
		 */
		setLoginAttributesOther: function(attributes) {
			this.setElementValue(this.managedItems.ldap_loginfilter_attributes.$element, attributes);
			this.managedItems.ldap_loginfilter_attributes.$element.multiselect('refresh');
		},

		/**
		 * sets the login list filter
		 *
		 * @param {string} filter
		 */
		setLoginFilter: function(filter) {
			this.setElementValue(this.managedItems.ldap_login_filter.$element, filter);
			this.$filterModeRawContainer.siblings('.ldapReadOnlyFilterContainer').find('.ldapFilterReadOnlyElement').text(filter);
		},

		/**
		 * updates the username attribute check box
		 *
		 * @param {string} useUsername contains an int
		 */
		setLoginAttributeUsername: function(useUsername) {
			this.setElementValue(
				this.managedItems.ldap_loginfilter_username.$element, useUsername
			);
		},

		/**
		 * updates the email attribute check box
		 *
		 * @param {string} useEmail contains an int
		 */
		setLoginAttributeEmail: function(useEmail) {
			this.setElementValue(
				this.managedItems.ldap_loginfilter_email.$element, useEmail
			);
		},

		/**
		 * presents the result of the login name test
		 *
		 * @param result
		 */
		handleLoginTestResult: function(result) {
			var message;
			var isHtml = false;
			if(result.status === 'success') {
				var usersFound = parseInt(result.changes.ldap_test_loginname, 10);
				if(usersFound < 1) {
					var filter = $('<p>').text(result.changes.ldap_test_effective_filter).html();
					message = t('user_ldap', 'User not found. Please check your login attributes and username. Effective filter (to copy-and-paste for command line validation): <br/>' + filter);
					console.warn(filter);
					isHtml = true;
				} else if(usersFound === 1) {
					message = t('user_ldap', 'User found and settings verified.');
				} else if(usersFound > 1) {
					message = t('user_ldap', 'Settings verified, but one user found. Only the first will be able to login. Consider a more narrow filter.');
				}
			} else {
				message = t('user_ldap', 'An unspecified error occurred. Please check the settings and the log.');
				if(!_.isUndefined(result.message) && result.message) {
					message = result.message;
				}
				if(message === 'Bad search filter') {
					message = t('user_ldap', 'The search filter is invalid, probably due to syntax issues like uneven number of opened and closed brackets. Please revise.');
				} else if(message === 'connection error') {
					message = t('user_ldap', 'A connection error to LDAP / AD occurred, please check host, port and credentials.');
				} else if(message === 'missing placeholder') {
					message = t('user_ldap', 'The %uid placeholder is missing. It will be replaced with the login name when querying LDAP / AD.');
				}
			}
			OC.Notification.showTemporary(message, {isHTML: isHtml});
		},

		/**
		 * @inheritdoc
		 */
		considerFeatureRequests: function() {
			if(!this.isActive) {
				return;
			}
			if(this.managedItems.ldap_loginfilter_attributes.$element.find('option').length === 0) {
				this.disableElement(this.managedItems.ldap_loginfilter_attributes.$element);
				if(this.parsedFilterMode === this.configModel.FILTER_MODE_ASSISTED) {
					this.configModel.requestWizard('ldap_loginfilter_attributes');
				}
			}
		},

		/**
		 * @inheritdoc
		 */
		onActivate: function() {
			this._super();
			this.considerFeatureRequests();
			if(!this.managedItems.ldap_login_filter.$element.val()) {
				this.configModel.requestWizard('ldap_login_filter');
			}
		},

		/**
		 * resets the view when a configuration switch happened.
		 *
		 * @param {WizardTabLoginFilter} view
		 * @param {Object} configuration
		 */
		onConfigSwitch: function(view, configuration) {
			view.managedItems.ldap_loginfilter_attributes.$element.find('option').remove();

			view.onConfigLoaded(view, configuration);
		},

		/**
		 * if UserObjectClasses are found, the corresponding element will be
		 * updated
		 *
		 * @param {WizardTabLoginFilter} view
		 * @param {FeaturePayload} payload
		 */
		onFeatureReceived: function(view, payload) {
			if(payload.feature === 'AvailableAttributes') {
				view.equipMultiSelect(view.managedItems.ldap_loginfilter_attributes.$element, payload.data);
			} else if(payload.feature === 'TestLoginName') {
				view.handleLoginTestResult(payload.data);
			}
		},

		/**
		 * request to test the  provided login name
		 *
		 * @param {Event} event
		 */
		onVerifyClick: function(event) {
			event.preventDefault();
			var testLogin = this.managedItems.ldap_test_loginname.$element.val();
			if(!testLogin) {
				OC.Notification.showTemporary(t('user_ldap', 'Please provide a login name to test against'), 3);
			} else {
				this.configModel.requestWizard('ldap_test_loginname', {ldap_test_loginname: testLogin});
			}
		},

		/**
		 * enables/disables the "Verify Settings" button, depending whether
		 * the corresponding text input has a value or not
		 */
		onTestLoginnameChange: function() {
			var loginName = this.managedItems.ldap_test_loginname.$element.val();
			var beDisabled = !_.isString(loginName) || !loginName.trim();
			if(beDisabled) {
				this.disableElement(this.managedItems.ldap_test_loginname.$relatedElements);
			} else {
				this.enableElement(this.managedItems.ldap_test_loginname.$relatedElements);
			}
		}

	});

	OCA.LDAP.Wizard.WizardTabLoginFilter = WizardTabLoginFilter;
})();
