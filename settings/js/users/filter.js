/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * @brief this object takes care of the filter functionality on the user
 * management page
 * @param {UserList} userList the UserList object
 * @param {GroupList} groupList the GroupList object
 */
function UserManagementFilter (userList, groupList) {
	this.userList = userList;
	this.groupList = groupList;
	this.oldFilter = '';

	this.init();
}

/**
 * @brief sets up when the filter action shall be triggered
 */
UserManagementFilter.prototype.init = function () {
	OC.Plugins.register('OCA.Search', this);
};

/**
 * @brief the filter action needs to be done, here the accurate steps are being
 * taken care of
 */
UserManagementFilter.prototype.run = _.debounce(function (filter) {
		if (filter === this.oldFilter) {
			return;
		}
		this.oldFilter = filter;
		this.userList.filter = filter;
		this.userList.empty();
		this.userList.update(GroupList.getCurrentGID());
		if (this.groupList.filterGroups) {
			// user counts are being updated nevertheless
			this.groupList.empty();
		}
		this.groupList.update();
	},
	300
);

/**
 * @brief returns the filter String
 * @returns string
 */
UserManagementFilter.prototype.getPattern = function () {
	var input = this.filterInput.val(),
		html = $('html'),
		isIE8or9 = html.hasClass('lte9');
	// FIXME - TODO - once support for IE8 and IE9 is dropped
	if (isIE8or9 && input == this.filterInput.attr('placeholder')) {
		input = '';
	}
	return input;
};

/**
 * @brief adds reset functionality to an HTML element
 * @param jQuery the jQuery representation of that element
 */
UserManagementFilter.prototype.addResetButton = function (button) {
	var umf = this;
	button.click(function () {
		umf.filterInput.val('');
		umf.run();
	});
};

UserManagementFilter.prototype.attach = function (search) {
	search.setFilter('settings', this.run.bind(this));
};
