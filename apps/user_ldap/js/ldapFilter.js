function LdapFilter(target)  {
	this.locked = true;
	this.target = false;
	this.mode = LdapWizard.filterModeAssisted;
	this.lazyRunCompose = false;

	if( target === 'User' ||
		target === 'Login' ||
		target === 'Group') {
		this.target = target;
		this.determineMode();
	}
}

LdapFilter.prototype.compose = function() {
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
		},
		function (result) {
			// error handling
		}
	);
}

LdapFilter.prototype.determineMode = function() {
	var param = 'action=get'+encodeURIComponent(this.target)+'FilterMode'+
		'&ldap_serverconfig_chooser='+
		encodeURIComponent($('#ldap_serverconfig_chooser').val());

	var filter = this;
	LdapWizard.ajax(param,
		function(result) {
			property = 'ldap' + filter.target + 'FilterMode';
			filter.mode = result.changes[property];
			if(filter.mode == LdapWizard.filterModeRaw
				&& $('#raw'+filter.target+'FilterContainer').hasClass('invisible')) {
				LdapWizard['toggleRaw'+filter.target+'Filter']();
			} else if(filter.mode == LdapWizard.filterModeAssisted
				&& !$('#raw'+filter.target+'FilterContainer').hasClass('invisible')) {
				LdapWizard['toggleRaw'+filter.target+'Filter']();
			}
			filter.unlock();
		},
		function (result) {
			//on error case get back to default i.e. Assisted
			if(!$('#raw'+filter.target+'FilterContainer').hasClass('invisible')) {
				LdapWizard['toggleRaw'+filter.target+'Filter']();
				filter.mode = LdapWizard.filterModeAssisted;
			}
		filter.unlock();
		}
	);

}

LdapFilter.prototype.unlock = function() {
	this.locked = false;
	if(this.lazyRunCompose) {
		this.lazyRunCompose = false;
		this.compose();
	}
}
