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
							if(parseInt(configvalue) === 1) {
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
					var html = '<option value="'+result.configPrefix+'" selected="selected">'+$('#ldap_serverconfig_chooser option').length+'. Server</option>';
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

	clearMappings: function(mappingSubject) {
		$.post(
			OC.filePath('user_ldap','ajax','clearMappings.php'),
			'ldap_clear_mapping='+mappingSubject,
			function(result) {
				if(result.status == 'success') {
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

	ajax: function(param, fnOnSuccess, fnOnError) {
		$.post(
			OC.filePath('user_ldap','ajax','wizard.php'),
			param,
			function(result) {
				if(result.status == 'success') {
					fnOnSuccess(result);
				} else {
					fnOnError(result);
				}
			}
		);
	},

	applyChanges: function (result) {
		for (id in result.changes) {
			if(!$.isArray(result.changes[id])) {
				//no need to blacklist multiselect
				LdapWizard.saveBlacklist[id] = true;
			}
			if(id.indexOf('count') > 0) {
				$('#'+id).text(result.changes[id]);
			} else {
				$('#'+id).val(result.changes[id]);
			}
		}
		LdapWizard.functionalityCheck();
	},

	checkBaseDN: function() {
		host = $('#ldap_host').val();
		port = $('#ldap_port').val();
		user = $('#ldap_dn').val();
		pass = $('#ldap_agent_password').val();

		if(host && port && user && pass) {
			param = 'action=guessBaseDN'+
					'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

			LdapWizard.ajax(param,
				function(result) {
					LdapWizard.applyChanges(result);
					if($('#ldap_base').val()) {
						$('#ldap_base').removeClass('invisible');
						LdapWizard.hideInfoBox();
					}
				},
				function (result) {
					$('#ldap_base').removeClass('invisible');
					LdapWizard.showInfoBox('Please specify a port');
				}
			);
		}
	},

	checkPort: function() {
		host = $('#ldap_host').val();
		user = $('#ldap_dn').val();
		pass = $('#ldap_agent_password').val();

		if(host && user && pass) {
			param = 'action=guessPortAndTLS'+
					'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

			LdapWizard.ajax(param,
				function(result) {
					LdapWizard.applyChanges(result);
					if($('#ldap_port').val()) {
						LdapWizard.checkBaseDN();
						$('#ldap_port').removeClass('invisible');
						LdapWizard.hideInfoBox();
					}
				},
				function (result) {
					$('#ldap_port').removeClass('invisible');
					LdapWizard.showInfoBox('Please specify the BaseDN');
				}
			);
		}
	},

	composeFilter: function(type) {
		if(type == 'user') {
			action = 'getUserListFilter';
		} else if(type == 'login') {
			action = 'getUserLoginFilter';
		} else if(type == 'group') {
			action = 'getGroupFilter';
		}

		param = 'action='+action+
				'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

		LdapWizard.ajax(param,
			function(result) {
				LdapWizard.applyChanges(result);
				if(type == 'user') {
					LdapWizard.countUsers();
				} else if(type == 'group') {
					LdapWizard.countGroups();
					LdapWizard.detectGroupMemberAssoc();
				}
			},
			function (result) {
				// error handling
			}
		);
	},

	_countThings: function(method) {
		param = 'action='+method+
				'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

		LdapWizard.ajax(param,
			function(result) {
				LdapWizard.applyChanges(result);
			},
			function (result) {
				// error handling
			}
		);
	},

	countGroups: function() {
		LdapWizard._countThings('countGroups');
	},

	countUsers: function() {
		LdapWizard._countThings('countUsers');
	},

	detectGroupMemberAssoc: function() {
		param = 'action=determineGroupMemberAssoc'+
				'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

		LdapWizard.ajax(param,
			function(result) {
				//pure background story
			},
			function (result) {
				// error handling
			}
		);
	},

	findAttributes: function() {
		param = 'action=determineAttributes'+
				'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

		LdapWizard.ajax(param,
			function(result) {
				$('#ldap_loginfilter_attributes').find('option').remove();
				for (i in result.options['ldap_loginfilter_attributes']) {
					//FIXME: move HTML into template
					attr = result.options['ldap_loginfilter_attributes'][i];
					$('#ldap_loginfilter_attributes').append(
								"<option value='"+attr+"'>"+attr+"</option>");
				}
				LdapWizard.applyChanges(result);
				$('#ldap_loginfilter_attributes').multiselect('refresh');
				$('#ldap_loginfilter_attributes').multiselect('enable');
			},
			function (result) {
				//deactivate if no attributes found
				$('#ldap_loginfilter_attributes').multiselect(
									{noneSelectedText : 'No attributes found'});
				$('#ldap_loginfilter_attributes').multiselect('disable');
			}
		);
	},

	findAvailableGroups: function(multisel, type) {
		if(type != 'Users' && type != 'Groups') {
			return false;
		}
		param = 'action=determineGroupsFor'+type+
				'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

		LdapWizard.ajax(param,
			function(result) {
				$('#'+multisel).find('option').remove();
				for (i in result.options[multisel]) {
					//FIXME: move HTML into template
					objc = result.options[multisel][i];
					$('#'+multisel).append("<option value='"+objc+"'>"+objc+"</option>");
				}
				LdapWizard.applyChanges(result);
				$('#'+multisel).multiselect('refresh');
				$('#'+multisel).multiselect('enable');
			},
			function (result) {
				$('#'+multisel).multiselect('disable');
			}
		);
	},

	findObjectClasses: function(multisel, type) {
		if(type != 'User' && type != 'Group') {
			return false;
		}
		param = 'action=determine'+type+'ObjectClasses'+
				'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

		LdapWizard.ajax(param,
			function(result) {
				$('#'+multisel).find('option').remove();
				for (i in result.options[multisel]) {
					//FIXME: move HTML into template
					objc = result.options[multisel][i];
					$('#'+multisel).append("<option value='"+objc+"'>"+objc+"</option>");
				}
				LdapWizard.applyChanges(result);
				$('#'+multisel).multiselect('refresh');
			},
			function (result) {
				//TODO: error handling
			}
		);
	},

	functionalityCheck: function() {
		//criterias to enable the connection:
		// - host, port, user filter, login filter
		host        = $('#ldap_host').val();
		port        = $('#ldap_port').val();
		userfilter  = $('#ldap_dn').val();
		loginfilter = $('#ldap_agent_password').val();

		//FIXME: activates a manually deactivated configuration.
		if(host && port && userfilter && loginfilter) {
			if($('#ldap_configuration_active').is(':checked')) {
				return;
			}
			$('#ldap_configuration_active').prop('checked', true);
			LdapWizard.save($('#ldap_configuration_active')[0]);
		} else {
			if($('#ldap_configuration_active').is(':checked')) {
				$('#ldap_configuration_active').prop('checked', false);
				LdapWizard.save($('#ldap_configuration_active')[0]);
			}
		}
	},

	hideInfoBox: function() {
		if(LdapWizard.checkInfoShown) {
			$('#ldapWizard1 .ldapWizardInfo').addClass('invisible');
			LdapWizard.checkInfoShown = false;
		}
	},

	init: function() {
		if($('#ldap_port').val()) {
			$('#ldap_port').removeClass('invisible');
		}
	},

	initGroupFilter: function() {
		LdapWizard.findObjectClasses('ldap_groupfilter_objectclass', 'Group');
		LdapWizard.findAvailableGroups('ldap_groupfilter_groups', 'Groups');
		LdapWizard.composeFilter('group');
		LdapWizard.countGroups();
	},

	initLoginFilter: function() {
		LdapWizard.findAttributes();
		LdapWizard.composeFilter('login');
	},

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

	initUserFilter: function() {
		LdapWizard.findObjectClasses('ldap_userfilter_objectclass', 'User');
		LdapWizard.findAvailableGroups('ldap_userfilter_groups', 'Users');
		LdapWizard.composeFilter('user');
		LdapWizard.countUsers();
	},

	onTabChange: function(event, ui) {
		if(ui.newTab[0].id === '#ldapWizard2') {
			LdapWizard.initUserFilter();
		} else if(ui.newTab[0].id === '#ldapWizard3') {
			LdapWizard.initLoginFilter();
		} else if(ui.newTab[0].id === '#ldapWizard4') {
			LdapWizard.initGroupFilter();
		}
	},

	processChanges: function(triggerObj) {
		if(triggerObj.id == 'ldap_host'
		   || triggerObj.id == 'ldap_port'
		   || triggerObj.id == 'ldap_dn'
		   || triggerObj.id == 'ldap_agent_password') {
			LdapWizard.checkPort();
			if($('#ldap_port').val()) {
				//if Port is already set, check BaseDN
				LdapWizard.checkBaseDN();
			}
		}

		if(triggerObj.id == 'ldap_userlist_filter') {
			LdapWizard.countUsers();
		} else if(triggerObj.id == 'ldap_group_filter') {
			LdapWizard.countGroups();
			LdapWizard.detectGroupMemberAssoc();
		}

		if(triggerObj.id == 'ldap_loginfilter_username'
		   || triggerObj.id == 'ldap_loginfilter_email') {
			LdapWizard.composeFilter('login');
		}
	},

	save: function(inputObj) {
		if(LdapWizard.saveBlacklist.hasOwnProperty(inputObj.id)) {
			delete LdapWizard.saveBlacklist[inputObj.id];
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

	saveMultiSelect: function(originalObj, resultObj) {
		values = '';
		for(i = 0; i < resultObj.length; i++) {
			values = values + "\n" + resultObj[i].value;
		}
		LdapWizard._save($('#'+originalObj)[0], $.trim(values));
		if(originalObj == 'ldap_userfilter_objectclass'
		   || originalObj == 'ldap_userfilter_groups') {
			LdapWizard.composeFilter('user');
			//when user filter is changed afterwards, login filter needs to
			//be adjusted, too
			LdapWizard.composeFilter('login');
		} else if(originalObj == 'ldap_loginfilter_attributes') {
			LdapWizard.composeFilter('login');
		} else if(originalObj == 'ldap_groupfilter_objectclass'
		   || originalObj == 'ldap_groupfilter_groups') {
			LdapWizard.composeFilter('group');
		}
	},

	_save: function(object, value) {
		param = 'cfgkey='+object.id+
				'&cfgval='+value+
				'&action=save'+
				'&ldap_serverconfig_chooser='+$('#ldap_serverconfig_chooser').val();

		$.post(
			OC.filePath('user_ldap','ajax','wizard.php'),
			param,
			function(result) {
				if(result.status == 'success') {
					LdapWizard.processChanges(object);
				} else {
// 					alert('Oooooooooooh :(');
				}
			}
		);
	},

	showInfoBox: function(text) {
		$('#ldapWizard1 .ldapWizardInfo').text(t('user_ldap', text));
		$('#ldapWizard1 .ldapWizardInfo').removeClass('invisible');
		LdapWizard.checkInfoShown = true;
	},

	toggleRawFilter: function(container, moc, mg, stateVar) {
		if($(container).hasClass('invisible')) {
			$(container).removeClass('invisible');
			$(moc).multiselect('disable');
			if($(mg).multiselect().attr('disabled') == 'disabled') {
				LdapWizard[stateVar] = 'disable';
			} else {
				LdapWizard[stateVar] = 'enable';
			}
			$(mg).multiselect('disable');
		} else {
			$(container).addClass('invisible');
			$(mg).multiselect(LdapWizard[stateVar]);
			$(moc).multiselect('enable');
		}
	},

	toggleRawGroupFilter: function() {
		LdapWizard.toggleRawFilter('#rawGroupFilterContainer',
								   '#ldap_groupfilter_objectclass',
								   '#ldap_groupfilter_groups',
								   'groupFilterGroupSelectState'
  								);
	},

	toggleRawUserFilter: function() {
		LdapWizard.toggleRawFilter('#rawUserFilterContainer',
								   '#ldap_userfilter_objectclass',
								   '#ldap_userfilter_groups',
								   'userFilterGroupSelectState'
  								);
	}
};

$(document).ready(function() {
	$('#ldapAdvancedAccordion').accordion({ heightStyle: 'content', animate: 'easeInOutCirc'});
	$('#ldapSettings').tabs({ beforeActivate: LdapWizard.onTabChange });
	$('#ldap_submit').button();
	$('#ldap_action_test_connection').button();
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
	LdapConfiguration.refreshConfig();
	$('#ldap_action_test_connection').click(function(event){
		event.preventDefault();
		$.post(
			OC.filePath('user_ldap','ajax','testConfiguration.php'),
			$('#ldap').serialize(),
			function (result) {
				if (result.status === 'success') {
					OC.dialogs.alert(
						result.message,
						t('user_ldap', 'Connection test succeeded')
					);
				} else {
					OC.dialogs.alert(
						result.message,
						t('user_ldap', 'Connection test failed')
					);
				}
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

	$('#ldap_submit').click(function(event) {
		event.preventDefault();
		$.post(
			OC.filePath('user_ldap','ajax','setConfiguration.php'),
			$('#ldap').serialize(),
			function (result) {
				bgcolor = $('#ldap_submit').css('background');
				if (result.status === 'success') {
					//the dealing with colors is a but ugly, but the jQuery version in use has issues with rgba colors
					$('#ldap_submit').css('background', '#fff');
					$('#ldap_submit').effect('highlight', {'color':'#A8FA87'}, 5000, function() {
						$('#ldap_submit').css('background', bgcolor);
					});
					//update the Label in the config chooser
					caption = $('#ldap_serverconfig_chooser option:selected:first').text();
					pretext = '. Server: ';
					caption = caption.slice(0, caption.indexOf(pretext) + pretext.length);
					caption = caption + $('#ldap_host').val();
					$('#ldap_serverconfig_chooser option:selected:first').text(caption);

				} else {
					$('#ldap_submit').css('background', '#fff');
					$('#ldap_submit').effect('highlight', {'color':'#E97'}, 5000, function() {
						$('#ldap_submit').css('background', bgcolor);
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
});
