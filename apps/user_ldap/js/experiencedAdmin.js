/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * controls behaviour depend on whether the admin is experienced in LDAP or not.
 *
 * @class
 * @param {object} wizard the LDAP Wizard object
 * @param {boolean} initialState whether the admin is experienced or not
 */
function ExperiencedAdmin(wizard, initialState) {
	this.wizard = wizard;
	this.isExperienced = false;
}


/**
 * toggles whether the admin is an experienced one or not
 *
 * @param {boolean} whether the admin is experienced or not
 */
ExperiencedAdmin.prototype.toggle = function(isExperienced) {
	this.isExperienced = isExperienced;
	if(this.isExperienced) {
		this.enableRawMode();
	}
};

/**
* answers whether the admin is an experienced one or not
*
* @return {boolean} whether the admin is experienced or not
*/
ExperiencedAdmin.prototype.isExperienced = function() {
	return this.isExperienced;
};

/**
 * switches all LDAP filters from Assisted to Raw mode.
 */
ExperiencedAdmin.prototype.enableRawMode = function	() {
	containers = {
		'toggleRawGroupFilter': '#rawGroupFilterContainer',
		'toggleRawLoginFilter': '#rawLoginFilterContainer',
		'toggleRawUserFilter' : '#rawUserFilterContainer'
	};

	for(method in containers) {
		if($(containers[method]).hasClass('invisible')) {
			this.wizard[method]();
		}
	};


};
