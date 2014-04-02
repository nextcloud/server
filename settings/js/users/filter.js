/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * @brief foobar
 * @param jQuery input element that works as the user text input field
 */
function UserManagementFilter(filterInput, userList) {
	this.filterInput = filterInput;
	this.userList = userList;
	this.thread = undefined;

	this.init();
}

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

UserManagementFilter.prototype.run = function() {
	this.userList.empty();
	this.userList.update();
}

UserManagementFilter.prototype.getPattern = function() {
	return this.filterInput.val();
}