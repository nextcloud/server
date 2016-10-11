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
	var WizardTabGroupFilter = OCA.LDAP.Wizard.WizardTabAbstractFilter.subClass({
		/**
		 * @inheritdoc
		 */
		init: function (fotf, tabIndex, tabID) {
			tabID = '#ldapWizard4';
			var items = {
				ldap_groupfilter_objectclass: {
					$element: $('#ldap_groupfilter_objectclass'),
					setMethod: 'setObjectClass',
					keyName: 'ldap_groupfilter_objectclass',
					featureName: 'GroupObjectClasses'
				},
				ldap_group_filter_mode: {
					setMethod: 'setFilterModeOnce'
				},
				ldap_groupfilter_groups: {
					$element: $('#ldap_groupfilter_groups'),
					setMethod: 'setGroups',
					keyName: 'ldap_groupfilter_groups',
					featureName: 'GroupsForGroups',
					$relatedElements: $(
						tabID + ' .ldapGroupListAvailable,' +
						tabID + ' .ldapGroupListSelected,' +
						tabID + ' .ldapManyGroupsSearch'
					)
				},
				ldap_group_filter: {
					$element: $('#ldap_group_filter'),
					setMethod: 'setFilter',
					keyName: 'ldap_group_filter'
				},
				groupFilterRawToggle: {
					$element: $('#toggleRawGroupFilter')
				},
				groupFilterRawContainer: {
					$element: $('#rawGroupFilterContainer')
				},
				ldap_group_count: {
					$element: $('#ldap_group_count'),
					$relatedElements: $('.ldapGetGroupCount'),
					setMethod: 'setCount',
					keyName: 'ldap_group_count'
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
			return this.managedItems.ldap_groupfilter_objectclass;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getGroupsItem: function () {
			return this.managedItems.ldap_groupfilter_groups;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getFilterItem: function () {
			return this.managedItems.ldap_group_filter;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getToggleItem: function () {
			return this.managedItems.groupFilterRawToggle;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getRawFilterContainerItem: function () {
			return this.managedItems.groupFilterRawContainer;
		},

		/**
		 * @inheritdoc
		 * @returns {Object}
		 */
		getCountItem: function () {
			return this.managedItems.ldap_group_count;
		},

		/**
		 * @inheritdoc
		 * @returns {string}
		 */
		getFilterModeKey: function () {
			return 'ldap_group_filter_mode';
		}

	});

	OCA.LDAP.Wizard.WizardTabGroupFilter = WizardTabGroupFilter;
})();
