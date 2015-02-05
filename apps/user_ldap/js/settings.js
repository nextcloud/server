var LdapConfiguration = {
	refreshConfig: function() {
		if($('#ldap_serverconfig_chooser option').length < 2) {
			LdapConfiguration.addConfiguration(true);
			return;
		}
		$.post(
			OC.filePath('user_ldap','ajax','getConfiguration.php'),
			$('#ldap_serverconfig_chooser').serialize(),
			function (result) {
				if(result.status === 'success') {
					$.each(result.configuration, function(configkey, configvalue) {
						elementID = '#'+configkey;

						//deal with Checkboxes
						if($(elementID).is('input[type=checkbox]')) {
							if(parseInt(configvalue, 10) === 1) {
								$(elementID).attr('checked', 'checked');
							} else {
								$(elementID).removeAttr('checked');
							}
							return;
						}

						//On Textareas, Multi-Line Settings come as array
						if($(elementID).is('textarea') && $.isArray(configvalue)) {
							configvalue = configvalue.join("\n");
						}

						// assign the value
						$('#'+configkey).val(configvalue);
					});
					LdapWizard.init();
				}
			}
		);
	},

	resetDefaults: function() {
		$('#ldap').find('input[type=text], input[type=number], input[type=password], textarea, select').each(function() {
			if($(this).attr('id') === 'ldap_serverconfig_chooser') {
				return;
			}
			$(this).val($(this).attr('data-default'));
		});
		$('#ldap').find('input[type=checkbox]').each(function() {
			if($(this).attr('data-default') === 1) {
				$(this).attr('checked', 'checked');
			} else {
				$(this).removeAttr('checked');
			}
		});
	},

	deleteConfiguration: function() {
		$.post(
			OC.filePath('user_ldap','ajax','deleteConfiguration.php'),
			$('#ldap_serverconfig_chooser').serialize(),
			function (result) {
				if(result.status === 'success') {
					$('#ldap_serverconfig_chooser option:selected').remove();
					$('#ldap_serverconfig_chooser option:first').select();
					LdapConfiguration.refreshConfig();
				} else {
					OC.dialogs.alert(
						result.message,
						t('user_ldap', 'Deletion failed')
					);
				}
			}
		);
	},

	addConfiguration: function(doNotAsk) {
		$.post(
			OC.filePath('user_ldap','ajax','getNewServerConfigPrefix.php'),
			function (result) {
				if(result.status === 'success') {
					if(doNotAsk) {
						LdapConfiguration.resetDefaults();
					} else {
						OC.dialogs.confirm(
							t('user_ldap', 'Take over settings from recent server configuration?'),
							t('user_ldap', 'Keep settings?'),
							function(keep) {
								if(!keep) {
									LdapConfiguration.resetDefaults();
								}
							}
						);
					}
					$('#ldap_serverconfig_chooser option:selected').removeAttr('selected');
					var html = '<option value="'+result.configPrefix+'" selected="selected">'+t('user_ldap','{nthServer}. Server', {nthServer: $('#ldap_serverconfig_chooser option').length})+'</option>';
					$('#ldap_serverconfig_chooser option:last').before(html);
					LdapWizard.init();
				} else {
					OC.dialogs.alert(
						result.message,
						t('user_ldap', 'Cannot add server configuration')
					);
				}
			}
		);
	},

	testConfiguration: function(onSuccess, onError) {
		$.post(
			OC.filePath('user_ldap','ajax','testConfiguration.php'),
			$('#ldap').serialize(),
			function (result) {
				if (result.status === 'success') {
					onSuccess(result);
				} else {
					onError(result);
				}
			}
		);
	},

	clearMappings: function(mappingSubject) {
		$.post(
			OC.filePath('user_ldap','ajax','clearMappings.php'),
			'ldap_clear_mapping='+encodeURIComponent(mappingSubject),
			function(result) {
				if(result.status === 'success') {
					OC.dialogs.info(
						t('user_ldap', 'mappings cleared'),
						t('user_ldap', 'Success')
					);
				} else {
					OC.dialogs.alert(
						result.message,
						t('user_ldap', 'Error')
					);
				}
			}
		);
	}
};

var LdapWizard = {
	checkPortInfoShown: false,
	saveBlacklist: {},
	userFilterGroupSelectState: 'enable',
	spinner: '<img class="wizSpinner" src="'+ OC.imagePath('core', 'loading.gif') +'">',
	filterModeAssisted: 0,
	filterModeRaw: 1,
	userFilter: false,
	loginFilter: false,
	groupFilter: false,
	ajaxRequests: {},
	lastTestSuccessful: true,

	ajax: function(param, fnOnSuccess, fnOnError, reqID) {
		if(!_.isUndefined(reqID)) {
			if(LdapWizard.ajaxRequests.hasOwnProperty(reqID)) {
				console.log('aborting ' + reqID);
				console.log(param);
				LdapWizard.ajaxRequests[reqID].abort();
			}
		}
		var request = $.post(
			OC.filePath('user_ldap','ajax','wizard.php'),
			param,
			function(result) {
				if(result.status === 'success') {
					fnOnSuccess(result);
				} else {
					fnOnError(result);
				}
			}
		);
		if(!_.isUndefined(reqID)) {
			LdapWizard.ajaxRequests[reqID] = request;
		}
		return request;
	},

	applyChanges: function (result) {
		for (var id in result.changes) {
			LdapWizard.blacklistAdd(id);
			if(id.indexOf('count') > 0) {
				$('#'+id).text(result.changes[id]);
			} else {
				$('#'+id).val(result.changes[id]);
			}
		}
		LdapWizard.functionalityCheck();

		if($('#ldapSettings').tabs('option', 'active') == 0) {
			LdapWizard.basicStatusCheck();
		}
	},

	enableTabs: function() {
		//do not use this function directly, use basicStatusCheck instead.
		if(LdapWizard.saveProcesses === 0) {
			$('.ldap_action_continue').removeAttr('disabled');
			$('.ldap_action_back').removeAttr('disabled');
			$('#ldapSettings').tabs('option', 'disabled', []);
		}
	},

	disableTabs: function() {
		$('.ldap_action_continue').attr('disabled', 'disabled');
		$('.ldap_action_back').attr('disabled', 'disabled');
		$('#ldapSettings').tabs('option', 'disabled', [1, 2, 3, 4, 5]);
	},

	basicStatusCheck: function() {
		//criteria to continue from the first tab
		// - host, port, user filter, agent dn, password, base dn
		var host  = $('#ldap_host').val();
		var port  = $('#ldap_port').val();
		var agent = $('#ldap_dn').val();
		var pwd   = $('#ldap_agent_password').val();
		var base  = $('#ldap_base').val();

		if((host && port  && base) && ((!agent && !pwd) || (agent && pwd))) {
			LdapWizard.enableTabs();
		} else {
			LdapWizard.disableTabs();
		}
	},


	blacklistAdd: function(id) {
		var obj = $('#' + id);
		if(!(obj[0].hasOwnProperty('multiple') && obj[0]['multiple'] === true)) {
			//no need to blacklist multiselect
			LdapWizard.saveBlacklist[id] = true;
			return true;
		}
		return false;
	},

	blacklistRemove: function(id) {
		if(LdapWizard.saveBlacklist.hasOwnProperty(id)) {
			delete LdapWizard.saveBlacklist[id];
			return true;
		}
		return false;
	},

	checkBaseDN: function() {
		var host = $('#ldap_host').val();
		var port = $('#ldap_port').val();
		var user = $('#ldap_dn').val();
		var pass = $('#ldap_agent_password').val();

		//FIXME: determine base dn with anonymous access
		if(host && port && user && pass) {
			var param = 'action=guessBaseDN'+
					'&ldap_serverconfig_chooser='+
					encodeURIComponent($('#ldap_serverconfig_chooser').val());

			LdapWizard.showSpinner('#ldap_base');
			$('#ldap_base').prop('disabled', 'disabled');
			LdapWizard.ajax(param,
				function(result) {
					LdapWizard.applyChanges(result);
					LdapWizard.hideSpinner('#ldap_base');
					if($('#ldap_base').val()) {
						LdapWizard.hideInfoBox();
					}
					$('#ldap_base').prop('disabled', false);
				},
				function (result) {
					LdapWizard.hideSpinner('#ldap_base');
					LdapWizard.showInfoBox(t('user_ldap', 'Please specify a Base DN'));
					LdapWizard.showInfoBox(t('user_ldap', 'Could not determine Base DN'));
					$('#ldap_base').prop('disabled', false);
				},
				'guessBaseDN'
			);
		}
	},

	checkPort: function() {
		var host = $('#ldap_host').val();
		var port = $('#ldap_port').val();

		if(host && !port) {
			var param = 'action=guessPortAndTLS'+
					'&ldap_serverconfig_chooser='+
					encodeURIComponent($('#ldap_serverconfig_chooser').val());

			LdapWizard.showSpinner('#ldap_port');
			$('#ldap_port').prop('disabled', 'disabled');
			LdapWizard.ajax(param,
				function(result) {
					LdapWizard.applyChanges(result);
					LdapWizard.hideSpinner('#ldap_port');
					if($('#ldap_port').val()) {
						LdapWizard.checkBaseDN();
						$('#ldap_port').prop('disabled', false);
						LdapWizard.hideInfoBox();
					}
				},
				function (result) {
					LdapWizard.hideSpinner('#ldap_port');
					$('#ldap_port').prop('disabled', false);
					LdapWizard.showInfoBox(t('user_ldap', 'Please specify the port'));
				},
				'guessPortAndTLS'
			);
		}
	},

	controlBack: function() {
		var curTabIndex = $('#ldapSettings').tabs('option', 'active');
		if(curTabIndex == 0) {
			return;
		}
		$('#ldapSettings').tabs('option', 'active', curTabIndex - 1);
		LdapWizard.controlUpdate(curTabIndex - 1);
	},

	controlContinue: function() {
		var curTabIndex = $('#ldapSettings').tabs('option', 'active');
		if(curTabIndex == 3) {
			return;
		}
		$('#ldapSettings').tabs('option', 'active', 1 + curTabIndex);
		LdapWizard.controlUpdate(curTabIndex + 1);
	},

	controlUpdate: function(nextTabIndex) {
		if(nextTabIndex == 0) {
			$('.ldap_action_back').addClass('invisible');
			$('.ldap_action_continue').removeClass('invisible');
		} else
		if(nextTabIndex == 1) {
			$('.ldap_action_back').removeClass('invisible');
			$('.ldap_action_continue').removeClass('invisible');
		} else
		if(nextTabIndex == 2) {
			$('.ldap_action_continue').removeClass('invisible');
			$('.ldap_action_back').removeClass('invisible');
		} else
		if(nextTabIndex == 3) {
			//now last tab
			$('.ldap_action_back').removeClass('invisible');
			$('.ldap_action_continue').addClass('invisible');
		}
	},

	_countThings: function(method, spinnerID, doneCallback) {
		var param = 'action='+method+
				'&ldap_serverconfig_chooser='+
				encodeURIComponent($('#ldap_serverconfig_chooser').val());

		LdapWizard.showSpinner(spinnerID);
		LdapWizard.ajax(param,
			function(result) {
				LdapWizard.applyChanges(result);
				LdapWizard.hideSpinner(spinnerID);
				if(!_.isUndefined(doneCallback)) {
					doneCallback(method);
				}
			},
			function (result) {
				OC.Notification.showTemporary('Counting the entries failed with: ' + result.message);
				LdapWizard.hideSpinner(spinnerID);
				if(!_.isUndefined(doneCallback)) {
					doneCallback(method);
				}
			},
			method
		);
	},

	countGroups: function(doneCallback) {
		var groupFilter  = $('#ldap_group_filter').val();
		if(!_.isEmpty(groupFilter)) {
			LdapWizard._countThings('countGroups', '#ldap_group_count', doneCallback);
		}
	},

	countUsers: function(doneCallback) {
		var userFilter  = $('#ldap_userlist_filter').val();
		if(!_.isEmpty(userFilter)) {
			LdapWizard._countThings('countUsers', '#ldap_user_count', doneCallback);
		}
	},

	/**
	 * called after detectors have run
	 * @callback runDetectorsCallback
	 */

	/**
	 * runs detectors to determine appropriate attributes, e.g. displayName
	 * @param {string} type either "User" or "Group"
	 * @param {runDetectorsCallback} triggered after all detectors have completed
	 */
	runDetectors: function(type, callback) {
		if(type === 'Group') {
			$.when(LdapWizard.detectGroupMemberAssoc())
				.then(callback, callback);
			if(   LdapWizard.admin.isExperienced
			   && !(LdapWizard.detectorsRunInXPMode & LdapWizard.groupDetectors)) {
				LdapWizard.detectorsRunInXPMode += LdapWizard.groupDetectors;
			}
		} else if(type === 'User') {
			var req1 = LdapWizard.detectUserDisplayNameAttribute();
			var req2 = LdapWizard.detectEmailAttribute();
			$.when(req1, req2)
				.then(callback, callback);
			if(   LdapWizard.admin.isExperienced
			   && !(LdapWizard.detectorsRunInXPMode & LdapWizard.userDetectors)) {
				LdapWizard.detectorsRunInXPMode += LdapWizard.userDetectors;
			}
		}
	},

	/**
	 * runs detector to find out a fitting user display name attribute
	 */
	detectUserDisplayNameAttribute: function() {
		var param = 'action=detectUserDisplayNameAttribute' +
			'&ldap_serverconfig_chooser='+
			encodeURIComponent($('#ldap_serverconfig_chooser').val());

		//runs in the background, no callbacks necessary
		return LdapWizard.ajax(param, LdapWizard.applyChanges, function(){}, 'detectUserDisplayNameAttribute');
	},

	detectEmailAttribute: function() {
		var param = 'action=detectEmailAttribute'+
				'&ldap_serverconfig_chooser='+
				encodeURIComponent($('#ldap_serverconfig_chooser').val());
		//runs in the background, no callbacks necessary
		return LdapWizard.ajax(param, LdapWizard.applyChanges, function(){}, 'detectEmailAttribute');
	},

	detectGroupMemberAssoc: function() {
		param = 'action=determineGroupMemberAssoc'+
				'&ldap_serverconfig_chooser='+
				encodeURIComponent($('#ldap_serverconfig_chooser').val());

		return LdapWizard.ajax(param,
			function(result) {
				//pure background story
			},
			function (result) {
				// error handling
			},
			'determineGroupMemberAssoc'
		);
	},

	findAttributes: function() {
		param = 'action=determineAttributes'+
				'&ldap_serverconfig_chooser='+
				encodeURIComponent($('#ldap_serverconfig_chooser').val());

		LdapWizard.showSpinner('#ldap_loginfilter_attributes');
		LdapWizard.ajax(param,
			function(result) {
				$('#ldap_loginfilter_attributes').find('option').remove();
				for (var i in result.options['ldap_loginfilter_attributes']) {
					//FIXME: move HTML into template
					var attr = result.options['ldap_loginfilter_attributes'][i];
					$('#ldap_loginfilter_attributes').append(
								"<option value='"+attr+"'>"+attr+"</option>");
				}
				LdapWizard.hideSpinner('#ldap_loginfilter_attributes');
				LdapWizard.applyChanges(result);
				$('#ldap_loginfilter_attributes').multiselect('refresh');
				if($('#rawLoginFilterContainer').hasClass('invisible')) {
					$('#ldap_loginfilter_attributes').multiselect('enable');
				}
				LdapWizard.postInitLoginFilter();
			},
			function (result) {
				//deactivate if no attributes found
				$('#ldap_loginfilter_attributes').multiselect(
									{noneSelectedText : 'No attributes found'});
				$('#ldap_loginfilter_attributes').multiselect('disable');
				LdapWizard.hideSpinner('#ldap_loginfilter_attributes');
			},
			'determineAttributes'
		);
	},

	findAvailableGroups: function(multisel, type) {
		if(type !== 'Users' && type !== 'Groups') {
			return false;
		}
		param = 'action=determineGroupsFor'+encodeURIComponent(type)+
				'&ldap_serverconfig_chooser='+
				encodeURIComponent($('#ldap_serverconfig_chooser').val());

		LdapWizard.showSpinner('#'+multisel);
		LdapWizard.ajax(param,
			function(result) {
				$('#'+multisel).find('option').remove();
				for (var i in result.options[multisel]) {
					//FIXME: move HTML into template
					objc = result.options[multisel][i];
					$('#'+multisel).append("<option value='"+objc+"'>"+objc+"</option>");
				}
				LdapWizard.hideSpinner('#'+multisel);
				LdapWizard.applyChanges(result);
				$('#'+multisel).multiselect('refresh');
				part = type.slice(0, -1);
				if($('#raw' + part + 'FilterContainer').hasClass('invisible')) {
					//enable only when raw filter editing is not turned on
					$('#'+multisel).multiselect('enable');
				}
				if(type === 'Users') {
					//required for initial save
					filter = $('#ldap_userlist_filter').val();
					if(!filter) {
						LdapWizard.saveMultiSelect(multisel,
									$('#'+multisel).multiselect("getChecked"));
					}
					LdapWizard.userFilterAvailableGroupsHasRun = true;
					LdapWizard.postInitUserFilter();
				}
			},
			function (result) {
				LdapWizard.hideSpinner('#'+multisel);
				$('#'+multisel).multiselect('disable');
				if(type === 'Users') {
					LdapWizard.userFilterAvailableGroupsHasRun = true;
					LdapWizard.postInitUserFilter();
				}
			},
			'findAvailableGroupsFor' + type
		);
	},

	findObjectClasses: function(multisel, type) {
		if(type !== 'User' && type !== 'Group') {
			return false;
		}
		var param = 'action=determine'+encodeURIComponent(type)+'ObjectClasses'+
				'&ldap_serverconfig_chooser='+
				encodeURIComponent($('#ldap_serverconfig_chooser').val());

		LdapWizard.showSpinner('#'+multisel);
		LdapWizard.ajax(param,
			function(result) {
				$('#'+multisel).find('option').remove();
				for (var i in result.options[multisel]) {
					//FIXME: move HTML into template
					objc = result.options[multisel][i];
					$('#'+multisel).append("<option value='"+objc+"'>"+objc+"</option>");
				}
				LdapWizard.hideSpinner('#'+multisel);
				LdapWizard.applyChanges(result);
				$('#'+multisel).multiselect('refresh');
				if(type === 'User') {
					//required for initial save
					filter = $('#ldap_userlist_filter').val();
					if(!filter) {
						LdapWizard.saveMultiSelect(multisel,
										$('#'+multisel).multiselect("getChecked"));
					}
					LdapWizard.userFilterObjectClassesHasRun = true;
					LdapWizard.postInitUserFilter();
				}
			},
			function (result) {
				LdapWizard.hideSpinner('#'+multisel);
				if(type === 'User') {
					LdapWizard.userFilterObjectClassesHasRun = true;
					LdapWizard.postInitUserFilter();
				}
				//TODO: error handling
			},
			'determine' + type + 'ObjectClasses'
		);
	},

	functionalityCheck: function() {
		//criteria to enable the connection:
		// - host, port, basedn, user filter, login filter
		var host        = $('#ldap_host').val();
		var port        = $('#ldap_port').val();
		var base        = $('#ldap_base').val();
		var userfilter  = $('#ldap_userlist_filter').val();
		var loginfilter = $('#ldap_login_filter').val();

		//FIXME: activates a manually deactivated configuration.
		if(host && port && base && userfilter && loginfilter) {
			LdapWizard.updateStatusIndicator(true);
			if($('#ldap_configuration_active').is(':checked')) {
				return;
			}
			if(!LdapWizard.isConfigurationActiveControlLocked) {
				//avoids a manually deactivated connection will be activated
				//upon opening the admin page
				$('#ldap_configuration_active').prop('checked', true);
				LdapWizard.save($('#ldap_configuration_active')[0]);
			}
		} else {
			if($('#ldap_configuration_active').is(':checked')) {
				$('#ldap_configuration_active').prop('checked', false);
				LdapWizard.save($('#ldap_configuration_active')[0]);
			}
			LdapWizard.updateStatusIndicator(false);
		}
	},

	hideInfoBox: function() {
		if(LdapWizard.checkInfoShown) {
			$('#ldapWizard1 .ldapWizardInfo').addClass('invisible');
			LdapWizard.checkInfoShown = false;
		}
	},

	hideSpinner: function(id) {
		$(id+' + .wizSpinner').remove();
		$(id + " + button").css('display', 'inline');
	},

	isConfigurationActiveControlLocked: true,
	detectorsRunInXPMode: 0,
	userDetectors: 1,
	groupDetectors: 2,

	init: function() {
		LdapWizard.detectorsRunInXPMode = 0;
		LdapWizard.instantiateFilters();
		LdapWizard.admin.setExperienced($('#ldap_experienced_admin').is(':checked'));
		LdapWizard.lastTestSuccessful = true;
		LdapWizard.basicStatusCheck();
		LdapWizard.functionalityCheck();
		LdapWizard.isConfigurationActiveControlLocked = false;
	},

	initGroupFilter: function() {
		LdapWizard.groupFilter.activate();
	},

	/** init login filter tab section **/

	initLoginFilter: function() {
		LdapWizard.loginFilter.activate();
	},

	postInitLoginFilter: function() {
		if($('#rawLoginFilterContainer').hasClass('invisible')) {
			LdapWizard.loginFilter.compose();
		}
	},

	/** end of init user filter tab section **/

	initMultiSelect: function(object, id, caption) {
		object.multiselect({
			header: false,
			selectedList: 9,
			noneSelectedText: caption,
			click: function(event, ui) {
				LdapWizard.saveMultiSelect(id,
										$('#'+id).multiselect("getChecked"));
			}
		});
	},

	hideTestSpinner:function (countMethod) {
		var selector;
		if(countMethod === 'countUsers') {
			selector = '#rawUserFilterContainer .ldapGetEntryCount';
		} else {
			selector = '#rawGroupFilterContainer .ldapGetEntryCount';
		}
		LdapWizard.hideSpinner(selector);
	},

	/** init user filter tab section **/

	instantiateFilters: function() {
		delete LdapWizard.userFilter;
		LdapWizard.userFilter = new LdapFilter('User', function(mode) {
			if( !LdapWizard.admin.isExperienced()
			   || mode === LdapWizard.filterModeAssisted) {
				LdapWizard.userFilter.updateCount();
			}
			LdapWizard.userFilter.findFeatures();
		});
		$('#rawUserFilterContainer .ldapGetEntryCount').click(function(event) {
			event.preventDefault();
			$('#ldap_user_count').text('');
			LdapWizard.showSpinner('#rawUserFilterContainer .ldapGetEntryCount');
			LdapWizard.userFilter.updateCount(LdapWizard.hideTestSpinner);
			$('#ldap_user_count').removeClass('hidden');
		});

		delete LdapWizard.loginFilter;
		LdapWizard.loginFilter = new LdapFilter('Login', function(mode) {
			LdapWizard.loginFilter.findFeatures();
		});

		delete LdapWizard.groupFilter;
		LdapWizard.groupFilter = new LdapFilter('Group', function(mode) {
			if( !LdapWizard.admin.isExperienced()
			   || mode === LdapWizard.filterModeAssisted) {
				LdapWizard.groupFilter.updateCount();
			}
			LdapWizard.groupFilter.findFeatures();
		});
		$('#rawGroupFilterContainer .ldapGetEntryCount').click(function(event) {
			event.preventDefault();
			$('#ldap_group_count').text('');
			LdapWizard.showSpinner('#rawGroupFilterContainer .ldapGetEntryCount');
			LdapWizard.groupFilter.updateCount(LdapWizard.hideTestSpinner);
			$('#ldap_group_count').removeClass('hidden');
		});
	},

	userFilterObjectClassesHasRun: false,
	userFilterAvailableGroupsHasRun: false,

	initUserFilter: function() {
		LdapWizard.userFilterObjectClassesHasRun = false;
		LdapWizard.userFilterAvailableGroupsHasRun = false;
		LdapWizard.userFilter.activate();
	},

	postInitUserFilter: function() {
		if(LdapWizard.userFilterObjectClassesHasRun &&
			LdapWizard.userFilterAvailableGroupsHasRun) {
			LdapWizard.userFilter.compose();
		}
	},

	/** end of init user filter tab section **/

	onTabChange: function(event, ui) {
		if(LdapWizard.saveProcesses  > 0) {
			//do not allow to switch tabs as long as a save process is active
			return false;
		}
		var newTabIndex = 0;
		if(ui.newTab[0].id === '#ldapWizard2') {
			LdapWizard.initUserFilter();
			newTabIndex = 1;
		} else if(ui.newTab[0].id === '#ldapWizard3') {
			LdapWizard.initLoginFilter();
			newTabIndex = 2;
		} else if(ui.newTab[0].id === '#ldapWizard4') {
			LdapWizard.initGroupFilter();
			newTabIndex = 3;
		}

		var curTabIndex = $('#ldapSettings').tabs('option', 'active');
		if(curTabIndex >= 0 && curTabIndex <= 3) {
			LdapWizard.controlUpdate(newTabIndex);
			//run detectors in XP mode, when "Test Filter" button has not been
			//clicked in order to make sure that email, displayname, member-
			//group association attributes are properly set.
			if(   curTabIndex === 1
			   && LdapWizard.admin.isExperienced
			   && !(LdapWizard.detecorsRunInXPMode & LdapWizard.userDetectors)
			) {
				LdapWizard.runDetectors('User', function(){});
			} else if(   curTabIndex === 3
			          && LdapWizard.admin.isExperienced
			          && !(LdapWizard.detecorsRunInXPMode & LdapWizard.groupDetectors)
			) {
				LdapWizard.runDetectors('Group', function(){});
			}
		}
	},

	/**
	 * allows UserFilter, LoginFilter and GroupFilter to lookup objectClasses
	 * and similar again. This should be called after essential changes, e.g.
	 * Host or BaseDN changes, or positive functionality check
	 *
	 */
	allowFilterFeatureSearch: function () {
		LdapWizard.userFilter.reAllowFeatureLookup();
		LdapWizard.loginFilter.reAllowFeatureLookup();
		LdapWizard.groupFilter.reAllowFeatureLookup();
	},

	processChanges: function (triggerObj) {
		LdapWizard.hideInfoBox();

		if(triggerObj.id === 'ldap_host'
		   || triggerObj.id === 'ldap_port'
		   || triggerObj.id === 'ldap_dn'
		   || triggerObj.id === 'ldap_agent_password') {
			LdapWizard.checkPort();
			if($('#ldap_port').val()) {
				//if Port is already set, check BaseDN
				LdapWizard.checkBaseDN();
				LdapWizard.allowFilterFeatureSearch();
			}
		}

		if(triggerObj.id === 'ldap_loginfilter_username'
		   || triggerObj.id === 'ldap_loginfilter_email') {
			LdapWizard.loginFilter.compose();
		} else if (!LdapWizard.admin.isExperienced()) {
			if(triggerObj.id === 'ldap_userlist_filter') {
				LdapWizard.userFilter.updateCount();
			} else if (triggerObj.id === 'ldap_group_filter') {
				LdapWizard.groupFilter.updateCount();
			}
		}

		if($('#ldapSettings').tabs('option', 'active') == 0) {
			LdapWizard.basicStatusCheck();
			LdapWizard.functionalityCheck();
		}
	},

	save: function(inputObj) {
		if(LdapWizard.blacklistRemove(inputObj.id)) {
			return;
		}
		if($(inputObj).is('input[type=checkbox]')
		   && !$(inputObj).is(':checked')) {
			val = 0;
		} else {
			val = $(inputObj).val();
		}
		LdapWizard._save(inputObj, val);
	},

	/**
	 * updates user or group count on multiSelect close. Resets the event
	 * function subsequently.
	 *
	 * @param {LdapFilter} filter
	 * @param {Object} $multiSelectObj
	 */
	onMultiSelectClose: function(filter, $multiSelectObj) {
		filter.updateCount();
		$multiSelectObj.multiselect({close: function(){}});
	},

	saveMultiSelect: function(originalObj, resultObj) {
		var values = '';
		for(var i = 0; i < resultObj.length; i++) {
			values = values + "\n" + resultObj[i].value;
		}
		LdapWizard._save($('#'+originalObj)[0], $.trim(values));
		var $multiSelectObj = $('#'+originalObj);
		var updateCount = !$multiSelectObj.multiselect("isOpen");
		var applyUpdateOnCloseToFilter;
		if(originalObj === 'ldap_userfilter_objectclass'
		   || originalObj === 'ldap_userfilter_groups') {
			LdapWizard.userFilter.compose(updateCount);
			if(!updateCount) {
				applyUpdateOnCloseToFilter = LdapWizard.userFilter;
			}
			//when user filter is changed afterwards, login filter needs to
			//be adjusted, too
			if(!LdapWizard.loginFilter) {
				LdapWizard.initLoginFilter();
			}
			LdapWizard.loginFilter.compose();
		} else if(originalObj === 'ldap_loginfilter_attributes') {
			LdapWizard.loginFilter.compose();
		} else if(originalObj === 'ldap_groupfilter_objectclass'
		   || originalObj === 'ldap_groupfilter_groups') {
			LdapWizard.groupFilter.compose(updateCount);
			if(!updateCount) {
				applyUpdateOnCloseToFilter = LdapWizard.groupFilter;
			}
		}

		if(applyUpdateOnCloseToFilter instanceof LdapFilter) {
			$multiSelectObj.multiselect({
				close: function () {
					LdapWizard.onMultiSelectClose(
						applyUpdateOnCloseToFilter, $multiSelectObj);
				}
			});
		}
	},

	saveProcesses: 0,
	_save: function(object, value) {
		$('#ldap .ldap_saving').removeClass('hidden');
		LdapWizard.saveProcesses += 1;
		$('#ldap *').addClass('save-cursor');
		param = 'cfgkey='+encodeURIComponent(object.id)+
				'&cfgval='+encodeURIComponent(value)+
				'&action=save'+
				'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

		$.post(
			OC.filePath('user_ldap','ajax','wizard.php'),
			param,
			function(result) {
				LdapWizard.saveProcesses -= 1;
				if(LdapWizard.saveProcesses === 0) {
					$('#ldap .ldap_saving').addClass('hidden');
					$('#ldap *').removeClass('save-cursor');
				}
				if(result.status === 'success') {
					LdapWizard.processChanges(object);
				} else {
					console.log('Could not save value for ' + object.id);
				}
			}
		);
	},

	showInfoBox: function(text) {
		$('#ldapWizard1 .ldapWizardInfo').text(text);
		$('#ldapWizard1 .ldapWizardInfo').removeClass('invisible');
		LdapWizard.checkInfoShown = true;
	},

	showSpinner: function(id) {
		if($(id + ' + .wizSpinner').length == 0) {
			$(LdapWizard.spinner).insertAfter($(id));
			$(id + " + img + button").css('display', 'none');
		}
	},

	toggleRawFilter: function(container, moc, mg, stateVar, modeKey) {
		var isUser = moc.indexOf('user') >= 0;
		var filter = isUser ? LdapWizard.userFilter : LdapWizard.groupFilter;
		//moc = multiselect objectclass
		//mg = mutliselect groups
		if($(container).hasClass('invisible')) {
			filter.setMode(LdapWizard.filterModeRaw);
			$(container).removeClass('invisible');
			$(moc).multiselect('disable');
			if($(mg).multiselect().attr('disabled') === 'disabled') {
				LdapWizard[stateVar] = 'disable';
			} else {
				LdapWizard[stateVar] = 'enable';
			}
			$(mg).multiselect('disable');
			LdapWizard._save({ id: modeKey }, LdapWizard.filterModeRaw);
		} else {
			filter.setMode(LdapWizard.filterModeAssisted);
			filter.findFeatures();
			$(container).addClass('invisible');
			$(mg).multiselect(LdapWizard[stateVar]);
			$(moc).multiselect('enable');
			LdapWizard._save({ id: modeKey }, LdapWizard.filterModeAssisted);
			if(isUser) {
				LdapWizard.blacklistRemove('ldap_userlist_filter');
				LdapWizard.userFilter.compose(true);
			} else {
				LdapWizard.blacklistRemove('ldap_group_filter');
				LdapWizard.groupFilter.compose(true);
			}
		}
	},

	onToggleRawFilterConfirmation: function(currentMode, isRawVisible, callback) {
		if(   !LdapWizard.admin.isExperienced()
		   || currentMode === LdapWizard.filterModeAssisted
		   || (LdapWizard.admin.isExperienced() && !isRawVisible)
		) {
			return callback(true);
		}

		var confirmed = OCdialogs.confirm(
			'Switching the mode will enable automatic LDAP queries. Depending on your LDAP size they may take a while. Do you still want to switch the mode?',
			'Mode switch',
			callback
		);
	},

	toggleRawGroupFilter: function() {
		LdapWizard.onToggleRawFilterConfirmation(
			LdapWizard.groupFilter.getMode(),
			!$('#rawGroupFilterContainer').hasClass('invisible'),
			function(confirmed) {
				if(confirmed !== true) {
					return;
				}

				LdapWizard.blacklistRemove('ldap_group_filter');
				LdapWizard.toggleRawFilter('#rawGroupFilterContainer',
										   '#ldap_groupfilter_objectclass',
										   '#ldap_groupfilter_groups',
										   'groupFilterGroupSelectState',
										   'ldapGroupFilterMode'
		  								);
				LdapWizard.admin.updateGroupTab(LdapWizard.groupFilter.getMode());
			}
		);
	},

	toggleRawLoginFilter: function() {
		LdapWizard.onToggleRawFilterConfirmation(
			LdapWizard.loginFilter.getMode(),
			!$('#rawLoginFilterContainer').hasClass('invisible'),
			function(confirmed) {
				if(confirmed !== true) {
					return;
				}

				LdapWizard.blacklistRemove('ldap_login_filter');
				container = '#rawLoginFilterContainer';
				if($(container).hasClass('invisible')) {
					$(container).removeClass('invisible');
					action = 'disable';
					property = 'disabled';
					mode = LdapWizard.filterModeRaw;
				} else {
					$(container).addClass('invisible');
					action = 'enable';
					property = false;
					mode = LdapWizard.filterModeAssisted;
				}
				LdapWizard.loginFilter.setMode(mode);
				LdapWizard.loginFilter.findFeatures();
				$('#ldap_loginfilter_attributes').multiselect(action);
				$('#ldap_loginfilter_email').prop('disabled', property);
				$('#ldap_loginfilter_username').prop('disabled', property);
				LdapWizard._save({ id: 'ldapLoginFilterMode' }, mode);
				if(action === 'enable') {
					LdapWizard.loginFilter.compose();
				}
			}
		);
	},

	toggleRawUserFilter: function() {
		LdapWizard.onToggleRawFilterConfirmation(
			LdapWizard.userFilter.getMode(),
			!$('#rawUserFilterContainer').hasClass('invisible'),
			function(confirmed) {
				if(confirmed === true) {
					LdapWizard.blacklistRemove('ldap_userlist_filter');
					LdapWizard.toggleRawFilter('#rawUserFilterContainer',
											   '#ldap_userfilter_objectclass',
											   '#ldap_userfilter_groups',
											   'userFilterGroupSelectState',
											   'ldapUserFilterMode'
			  								);
					LdapWizard.admin.updateUserTab(LdapWizard.userFilter.getMode());
				}
			}
		);
	},

	updateStatusIndicator: function(isComplete) {
		if(isComplete) {
			LdapConfiguration.testConfiguration(
				//onSuccess
				function(result) {
					$('.ldap_config_state_indicator').text(t('user_ldap',
						'Configuration OK'
					));
					$('.ldap_config_state_indicator').addClass('ldap_grey');
					$('.ldap_config_state_indicator_sign').removeClass('error');
					$('.ldap_config_state_indicator_sign').addClass('success');
					if(!LdapWizard.lastTestSuccessful) {
						LdapWizard.lastTestSuccessful = true;
						LdapWizard.allowFilterFeatureSearch();
					}
				},
				//onError
				function(result) {
					$('.ldap_config_state_indicator').text(t('user_ldap',
						'Configuration incorrect'
					));
					$('.ldap_config_state_indicator').removeClass('ldap_grey');
					$('.ldap_config_state_indicator_sign').addClass('error');
					$('.ldap_config_state_indicator_sign').removeClass('success');
					LdapWizard.lastTestSuccessful = false;
				}
			);
		} else {
			$('.ldap_config_state_indicator').text(t('user_ldap',
				'Configuration incomplete'
			));
			$('.ldap_config_state_indicator').removeClass('ldap_grey');
			$('.ldap_config_state_indicator_sign').removeClass('error');
			$('.ldap_config_state_indicator_sign').removeClass('success');
		}
	}
};

$(document).ready(function() {
	$('#ldapAdvancedAccordion').accordion({ heightStyle: 'content', animate: 'easeInOutCirc'});
	$('#ldapSettings').tabs({ beforeActivate: LdapWizard.onTabChange });
	$('.ldap_submit').button();
	$('.ldap_action_test_connection').button();
	$('#ldap_action_delete_configuration').button();
	LdapWizard.initMultiSelect($('#ldap_userfilter_groups'),
							   'ldap_userfilter_groups',
							   t('user_ldap', 'Select groups'));
	LdapWizard.initMultiSelect($('#ldap_userfilter_objectclass'),
							   'ldap_userfilter_objectclass',
							   t('user_ldap', 'Select object classes'));
	LdapWizard.initMultiSelect($('#ldap_loginfilter_attributes'),
							   'ldap_loginfilter_attributes',
							   t('user_ldap', 'Select attributes'));
	LdapWizard.initMultiSelect($('#ldap_groupfilter_groups'),
							   'ldap_groupfilter_groups',
							   t('user_ldap', 'Select groups'));
	LdapWizard.initMultiSelect($('#ldap_groupfilter_objectclass'),
							   'ldap_groupfilter_objectclass',
							   t('user_ldap', 'Select object classes'));

	$('.lwautosave').change(function() { LdapWizard.save(this); });
	$('#toggleRawUserFilter').click(LdapWizard.toggleRawUserFilter);
	$('#toggleRawGroupFilter').click(LdapWizard.toggleRawGroupFilter);
	$('#toggleRawLoginFilter').click(LdapWizard.toggleRawLoginFilter);
	LdapConfiguration.refreshConfig();
	$('.ldap_action_continue').click(function(event) {
		event.preventDefault();
		LdapWizard.controlContinue();
	});
	$('.ldap_action_back').click(function(event) {
		event.preventDefault();
		LdapWizard.controlBack();
	});
	$('.ldap_action_test_connection').click(function(event){
		event.preventDefault();
		LdapConfiguration.testConfiguration(
			//onSuccess
			function(result) {
				OC.dialogs.alert(
					result.message,
					t('user_ldap', 'Connection test succeeded')
				);
			},
			//onError
			function(result) {
				OC.dialogs.alert(
					result.message,
					t('user_ldap', 'Connection test failed')
				);
			}
		);
	});

	$('#ldap_action_delete_configuration').click(function(event) {
		event.preventDefault();
		OC.dialogs.confirm(
			t('user_ldap', 'Do you really want to delete the current Server Configuration?'),
			t('user_ldap', 'Confirm Deletion'),
			function(deleteConfiguration) {
				if(deleteConfiguration) {
					LdapConfiguration.deleteConfiguration();
				}
			}
		);
	});

	$('.ldap_submit').click(function(event) {
		event.preventDefault();
		$.post(
			OC.filePath('user_ldap','ajax','setConfiguration.php'),
			$('#ldap').serialize(),
			function (result) {
				bgcolor = $('.ldap_submit').css('background');
				if (result.status === 'success') {
					//the dealing with colors is a but ugly, but the jQuery version in use has issues with rgba colors
					$('.ldap_submit').css('background', '#fff');
					$('.ldap_submit').effect('highlight', {'color':'#A8FA87'}, 5000, function() {
						$('.ldap_submit').css('background', bgcolor);
					});
					//update the Label in the config chooser
					caption = $('#ldap_serverconfig_chooser option:selected:first').text();
					pretext = '. Server: ';
					caption = caption.slice(0, caption.indexOf(pretext) + pretext.length);
					caption = caption + $('#ldap_host').val();
					$('#ldap_serverconfig_chooser option:selected:first').text(caption);

				} else {
					$('.ldap_submit').css('background', '#fff');
					$('.ldap_submit').effect('highlight', {'color':'#E97'}, 5000, function() {
						$('.ldap_submit').css('background', bgcolor);
					});
				}
			}
		);
	});

	$('#ldap_action_clear_user_mappings').click(function(event) {
		event.preventDefault();
		LdapConfiguration.clearMappings('user');
	});

	$('#ldap_action_clear_group_mappings').click(function(event) {
		event.preventDefault();
		LdapConfiguration.clearMappings('group');
	});

	$('#ldap_serverconfig_chooser').change(function(event) {
		value = $('#ldap_serverconfig_chooser option:selected:first').attr('value');
		if(value === 'NEW') {
			LdapConfiguration.addConfiguration(false);
		} else {
			LdapConfiguration.refreshConfig();
		}
	});

	expAdminCB = $('#ldap_experienced_admin');
	LdapWizard.admin = new ExperiencedAdmin(LdapWizard, expAdminCB.is(':checked'));
	expAdminCB.change(function() {
		LdapWizard.admin.setExperienced($(this).is(':checked'));
	});
});
