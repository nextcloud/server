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
	var WizardTabUserFilter = OCA.LDAP.Wizard.WizardTabAbstractFilter.subClass({
		/**
		 * @inheritdoc
		 */
		init: function (fotf, tabIndex, tabID) {
			tabID = '#ldapWizard2';
			var items = {
				ldap_userfilter_objectclass: {
					$element: $('#ldap_userfilter_objectclass'),
					setMethod: 'setObjectClass',
					keyName: 'ldap_userfilter_objectclass',
					featureName: 'UserObjectClasses'
				},
				ldap_user_filter_mode: {
					setMethod: 'setFilterModeOnce'
				},
				ldap_userfilter_groups: {
					$element: $('#ldap_userfilter_groups'),
					setMethod: 'setGroups',
					keyName: 'ldap_userfilter_groups',
					featureName: 'GroupsForUsers',
					$relatedElements: $(
						tabID + ' .ldapGroupListAvailable,' +
						tabID + ' .ldapGroupListSelected,' +
						tabID + ' .ldapManyGroupsSearch'
					)
				},
				ldap_userlist_filter: {
					$element: $('#ldap_userlist_filter'),
					setMethod: 'setFilter',
					keyName: 'ldap_userlist_filter'
				},
				userFilterRawToggle: {
					$element: $('#toggleRawUserFilter')
				},
				userFilterRawContainer: {
					$element: $('#rawUserFilterContainer')
				},
				ldap_user_count: {
					$element: $('#ldap_user_count'),
					$relatedElements: $('.ldapGetUserCount'),
					setMethod: 'setCount',
					keyName: 'ldap_user_count'
				}
			};
			this.setManagedItems(items);
			this.manyGroupsSupport = true;
			this._super(fotf, tabIndex, tabID);
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getObjectClassItem: function () {
			return this.managedItems.ldap_userfilter_objectclass;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getGroupsItem: function () {
			return this.managedItems.ldap_userfilter_groups;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getFilterItem: function () {
			return this.managedItems.ldap_userlist_filter;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getToggleItem: function () {
			return this.managedItems.userFilterRawToggle;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getRawFilterContainerItem: function () {
			return this.managedItems.userFilterRawContainer;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getCountItem: function () {
			return this.managedItems.ldap_user_count;
		},

		/**
		 * @inheritdoc
		 * @returns {string}
		 */
		getFilterModeKey: function () {
			return 'ldap_user_filter_mode';
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
			if(   key === 'ldap_userfilter_groups'
			   && message === 'memberOf is not supported by the server'
			) {
				message = t('user_ldap', 'The group box was disabled, because the LDAP / AD server does not support memberOf.');
			}
			return message;
		}

	});

	OCA.LDAP.Wizard.WizardTabUserFilter = WizardTabUserFilter;
})();
