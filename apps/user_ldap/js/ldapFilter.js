/* global LdapWizard */

function LdapFilter(target, determineModeCallback) {
	this.locked = true;
	this.target = false;
	this.mode = LdapWizard.filterModeAssisted;
	this.lazyRunCompose = false;
	this.determineModeCallback = determineModeCallback;
	this.foundFeatures = false;
	this.activated = false;
	this.countPending = false;

	if( target === 'User' ||
		target === 'Login' ||
		target === 'Group') {
		this.target = target;
	}
}

LdapFilter.prototype.activate = function() {
	if(this.activated) {
		// might be necessary, if configuration changes happened.
		this.findFeatures();
		return;
	}
	this.activated = true;

	this.determineMode();
};

LdapFilter.prototype.compose = function(updateCount) {
	var action;

	if(updateCount === true) {
		this.countPending = updateCount;
	}

	if(this.locked) {
		this.lazyRunCompose = true;
		return false;
	}

	if(this.mode === LdapWizard.filterModeRaw) {
		//Raw filter editing, i.e. user defined filter, don't compose
		return;
	}

	if(this.target === 'User') {
		action = 'getUserListFilter';
	} else if(this.target === 'Login') {
		action = 'getUserLoginFilter';
	} else if(this.target === 'Group') {
		action = 'getGroupFilter';
	}

	var param = 'action='+action+
		'&ldap_serverconfig_chooser='+
		encodeURIComponent($('#ldap_serverconfig_chooser').val());

	var filter = this;

	LdapWizard.ajax(param,
		function(result) {
			filter.afterComposeSuccess(result);
		},
		function () {
			filter.countPending = false;
			console.log('LDAP Wizard: could not compose filter. '+
				'Please check owncloud.log');
		}
	);
};

/**
 * this function is triggered after LDAP filters have been composed successfully
 * @param {object} result returned by the ajax call
 */
LdapFilter.prototype.afterComposeSuccess = function(result) {
	LdapWizard.applyChanges(result);
	if(this.countPending) {
		this.countPending = false;
		this.updateCount();
	}
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
			var rawContainerIsInvisible =
				$('#raw'+filter.target+'FilterContainer').hasClass('invisible');
			if (   filter.mode === LdapWizard.filterModeRaw
				&& rawContainerIsInvisible
			) {
				LdapWizard['toggleRaw'+filter.target+'Filter']();
			} else if (    filter.mode === LdapWizard.filterModeAssisted
						&& !rawContainerIsInvisible
			) {
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
};

LdapFilter.prototype.getMode = function() {
	return this.mode;
};

LdapFilter.prototype.unlock = function() {
	this.locked = false;
	if(this.lazyRunCompose) {
		this.lazyRunCompose = false;
		this.compose();
	}
};

/**
 * resets this.foundFeatures so that LDAP queries can be fired again to retrieve
 * objectClasses, groups, etc.
 */
LdapFilter.prototype.reAllowFeatureLookup = function () {
	this.foundFeatures = false;
};

LdapFilter.prototype.findFeatures = function() {
	if(!this.foundFeatures && !this.locked && this.mode === LdapWizard.filterModeAssisted) {
		this.foundFeatures = true;
		var objcEl, avgrEl;
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
};

/**
 * this function is triggered before user and group counts are executed
 * resolving the passed status variable will fire up counting
 */
LdapFilter.prototype.beforeUpdateCount = function() {
	var status = $.Deferred();
	LdapWizard.runDetectors(this.target, function() {
		status.resolve();
	});
	return status;
};

LdapFilter.prototype.updateCount = function(doneCallback) {
	var filter = this;
	$.when(this.beforeUpdateCount()).done(function() {
		if(filter.target === 'User') {
			LdapWizard.countUsers(doneCallback);
		} else if (filter.target === 'Group') {
			LdapWizard.countGroups(doneCallback);
		}
	});
};
