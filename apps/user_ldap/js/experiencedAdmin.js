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
	this.isExperienced = initialState;
	if(this.isExperienced) {
		this.hideEntryCounters();
	}
}


/**
 * toggles whether the admin is an experienced one or not
 *
 * @param {boolean} whether the admin is experienced or not
 */
ExperiencedAdmin.prototype.setExperienced = function(isExperienced) {
	this.isExperienced = isExperienced;
	if(this.isExperienced) {
		this.enableRawMode();
		this.hideEntryCounters();
	} else {
		this.showEntryCounters();
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
ExperiencedAdmin.prototype.enableRawMode = function() {
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

ExperiencedAdmin.prototype.updateUserTab = function(mode) {
	this._updateTab(mode, $('#ldap_user_count'));
}

ExperiencedAdmin.prototype.updateGroupTab = function(mode) {
	this._updateTab(mode, $('#ldap_group_count'));
}

ExperiencedAdmin.prototype._updateTab = function(mode, $countEl) {
	if(mode === LdapWizard.filterModeAssisted) {
		$countEl.removeClass('hidden');
	} else if(!this.isExperienced) {
		$countEl.removeClass('hidden');
	} else {
		$countEl.addClass('hidden');
	}
}

/**
 * hide user and group counters, they will be displayed on demand only
 */
ExperiencedAdmin.prototype.hideEntryCounters = function() {
	$('#ldap_user_count').addClass('hidden');
	$('#ldap_group_count').addClass('hidden');
	$('.ldapGetEntryCount').removeClass('hidden');
};

/**
* shows user and group counters, they will be displayed on demand only
*/
ExperiencedAdmin.prototype.showEntryCounters = function() {
	$('#ldap_user_count').removeClass('hidden');
	$('#ldap_group_count').removeClass('hidden');
	$('.ldapGetEntryCount').addClass('hidden');
};
