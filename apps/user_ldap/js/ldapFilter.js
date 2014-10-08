/* global LdapWizard */

function LdapFilter(target, determineModeCallback)  {
	this.locked = true;
	this.target = false;
	this.mode = LdapWizard.filterModeAssisted;
	this.lazyRunCompose = false;
	this.determineModeCallback = determineModeCallback;
	this.foundFeatures = false;
	this.activated = false;

	if( target === 'User' ||
		target === 'Login' ||
		target === 'Group') {
		this.target = target;
	}
}

LdapFilter.prototype.activate = function() {
	if(this.activated) {
		return;
	}
	this.activated = true;

	this.determineMode();
}

LdapFilter.prototype.compose = function(callback) {
	var action;

	if(this.locked) {
		this.lazyRunCompose = true;
		return false;
	}

	if(this.target === 'User') {
		action = 'getUserListFilter';
	} else if(this.target === 'Login') {
		action = 'getUserLoginFilter';
	} else if(this.target === 'Group') {
		action = 'getGroupFilter';
	}

	if(!$('#raw'+this.target+'FilterContainer').hasClass('invisible')) {
		//Raw filter editing, i.e. user defined filter, don't compose
		return;
	}

	var param = 'action='+action+
		'&ldap_serverconfig_chooser='+
		encodeURIComponent($('#ldap_serverconfig_chooser').val());

	var filter = this;

	LdapWizard.ajax(param,
		function(result) {
			LdapWizard.applyChanges(result);
			if(filter.target === 'User') {
				LdapWizard.countUsers();
			} else if(filter.target === 'Group') {
				LdapWizard.countGroups();
				LdapWizard.detectGroupMemberAssoc();
			}
			if(typeof callback !== 'undefined') {
				callback();
			}
		},
		function () {
			console.log('LDAP Wizard: could not compose filter. '+
				'Please check owncloud.log');
		}
	);
};

LdapFilter.prototype.determineMode = function() {
	var param = 'action=get'+encodeURIComponent(this.target)+'FilterMode'+
		'&ldap_serverconfig_chooser='+
		encodeURIComponent($('#ldap_serverconfig_chooser').val());

	var filter = this;
	LdapWizard.ajax(param,
		function(result) {
			var property = 'ldap' + filter.target + 'FilterMode';
			filter.mode = parseInt(result.changes[property], 10);
			if(filter.mode === LdapWizard.filterModeRaw &&
				$('#raw'+filter.target+'FilterContainer').hasClass('invisible')) {
				LdapWizard['toggleRaw'+filter.target+'Filter']();
			} else if(filter.mode === LdapWizard.filterModeAssisted &&
				!$('#raw'+filter.target+'FilterContainer').hasClass('invisible')) {
				LdapWizard['toggleRaw'+filter.target+'Filter']();
			} else {
				console.log('LDAP Wizard determineMode: returned mode was »' +
					filter.mode + '« of type ' + typeof filter.mode);
			}
			filter.unlock();
			filter.determineModeCallback(filter.mode);
		},
		function () {
			//on error case get back to default i.e. Assisted
			if(!$('#raw'+filter.target+'FilterContainer').hasClass('invisible')) {
				LdapWizard['toggleRaw'+filter.target+'Filter']();
				filter.mode = LdapWizard.filterModeAssisted;
			}
			filter.unlock();
			filter.determineModeCallback(filter.mode);
		}
	);
};

LdapFilter.prototype.setMode = function(mode) {
	if(mode === LdapWizard.filterModeAssisted || mode === LdapWizard.filterModeRaw) {
		this.mode = mode;
	}
}

LdapFilter.prototype.unlock = function() {
	this.locked = false;
	if(this.lazyRunCompose) {
		this.lazyRunCompose = false;
		this.compose();
	}
};

LdapFilter.prototype.findFeatures = function() {
	if(!this.foundFeatures && !this.locked && this.mode === LdapWizard.filterModeAssisted) {
		this.foundFeatures = true;
		if(this.target === 'User') {
			objcEl = 'ldap_userfilter_objectclass';
			avgrEl = 'ldap_userfilter_groups';
		} else if (this.target === 'Group') {
			objcEl = 'ldap_groupfilter_objectclass';
			avgrEl = 'ldap_groupfilter_groups';
		} else if (this.target === 'Login') {
			LdapWizard.findAttributes();
			return;
		} else {
			return false;
		}
		LdapWizard.findObjectClasses(objcEl, this.target);
		LdapWizard.findAvailableGroups(avgrEl, this.target + "s");
	}
}
