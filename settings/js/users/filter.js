/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * @brief this object takes care of the filter functionality on the user
 * management page
 * @param jQuery input element that works as the user text input field
 * @param object the UserList object
 */
function UserManagementFilter(filterInput, userList, groupList) {
	this.filterInput = filterInput;
	this.userList = userList;
	this.groupList = groupList;
	this.filterGroups = false;
	this.thread = undefined;
	this.oldval = this.filterInput.val();

	this.init();
}

/**
 * @brief sets up when the filter action shall be triggered
 */
UserManagementFilter.prototype.init = function() {
	var umf = this;
	this.filterInput.keyup(function(e) {
		//we want to react on any printable letter, plus on modifying stuff like
		//Backspace and Delete. extended https://stackoverflow.com/a/12467610
		var valid =
			e.keyCode ===  0 || e.keyCode ===  8  || // like ö or ж; backspace
			e.keyCode ===  9 || e.keyCode === 46  || // tab; delete
			e.keyCode === 32                      || // space
			(e.keyCode >  47 && e.keyCode <   58) || // number keys
			(e.keyCode >  64 && e.keyCode <   91) || // letter keys
			(e.keyCode >  95 && e.keyCode <  112) || // numpad keys
			(e.keyCode > 185 && e.keyCode <  193) || // ;=,-./` (in order)
			(e.keyCode > 218 && e.keyCode <  223);   // [\]' (in order)

		//besides the keys, the value must have been changed compared to last
		//time
		if(valid && umf.oldVal !== umf.getPattern()) {
			umf.run();
		}

		umf.oldVal = umf.getPattern();
	});
};

/**
 * @brief the filter action needs to be done, here the accurate steps are being
 * taken care of
 */
UserManagementFilter.prototype.run = _.debounce(function() {
		this.userList.empty();
		this.userList.update(GroupList.getCurrentGID());
		if(this.filterGroups) {
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
UserManagementFilter.prototype.getPattern = function() {
	var input = this.filterInput.val(),
		html = $('html'),
		isIE8or9 = html.hasClass('lte9');
	// FIXME - TODO - once support for IE8 and IE9 is dropped
	if(isIE8or9 && input == this.filterInput.attr('placeholder')) {
		input = '';
	}
	return input;
};

/**
 * @brief adds reset functionality to an HTML element
 * @param jQuery the jQuery representation of that element
 */
UserManagementFilter.prototype.addResetButton = function(button) {
	var umf = this;
	button.click(function(){
		umf.filterInput.val('');
		umf.run();
	});
};
