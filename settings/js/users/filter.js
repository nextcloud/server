/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * @brief this object takes care of the filter funcationality on the user
 * management page
 * @param jQuery input element that works as the user text input field
 * @param object the UserList object
 */
function UserManagementFilter(filterInput, userList) {
	this.filterInput = filterInput;
	this.userList = userList;
	this.thread = undefined;

	this.init();
}

/**
 * @brief sets up when the filter action shall be triggered
 */
UserManagementFilter.prototype.init = function() {
	umf = this;
	this.filterInput.keyup(function() {
		clearTimeout(umf.thread);
		umf.thread = setTimeout(
			function() {
				umf.run();
			},
			300
		);
	});
}

/**
 * @brief the filter action needs to be done, here the accurate steps are being
 * taken care of
 */
UserManagementFilter.prototype.run = function() {
	this.userList.empty();
	this.userList.update();
}

/**
 * @brief returns the filter String
 * @returns string
 */
UserManagementFilter.prototype.getPattern = function() {
	return this.filterInput.val();
}