$(document).ready(function() {
	$('#ldapSettings').tabs();
	$('#ldap_action_test_connection').button();
	$('#ldap_action_test_connection').click(function(event){
		event.preventDefault();
		$.post(
			OC.filePath('user_ldap','ajax','testConfiguration.php'),
			$('#ldap').serialize(),
			function (result) {
				if (result.status == 'success') {
					OC.dialogs.alert(
						result.message,
						'Connection test succeeded'
					);
				} else {
					OC.dialogs.alert(
						result.message,
						'Connection test failed'
					);
				}
			}
		);
	});

	$('#ldap_serverconfig_chooser').change(function(event) {
		value = $('#ldap_serverconfig_chooser option:selected:first').attr('value');
		if(value == 'NEW') {
			$.post(
				OC.filePath('user_ldap','ajax','getNewServerConfigPrefix.php'),
				function (result) {
					if(result.status == 'success') {
						OC.dialogs.confirm(
							'Take over settings from recent server configuration?',
							'Keep settings?',
							function(keep) {
								if(!keep) {
									$('#ldap').find('input[type=text], input[type=number], input[type=password], textarea, select').each(function() {
										$(this).val($(this).attr('data-default'));
									});
									$('#ldap').find('input[type=checkbox]').each(function() {
										if($(this).attr('data-default') == 1) {
											$(this).attr('checked', 'checked');
										} else {
											$(this).removeAttr('checked');
										}
									});
								}
							}
						);
						$('#ldap_serverconfig_chooser option:selected:first').removeAttr('selected');
						var html = '<option value="'+result.configPrefix+'" selected>'+$('#ldap_serverconfig_chooser option').length+'. Server</option>';
						$('#ldap_serverconfig_chooser option:last').before(html);
					} else {
						OC.dialogs.alert(
							result.message,
							'Cannot add server configuration'
						);
					}
				}
			);
		} else {
			alert(value);
		}
	});
});