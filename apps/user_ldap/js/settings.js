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
					$('#ldap_action_test_connection').css('background-color', 'red');
					OC.dialogs.alert(
						result.message,
						'Connection test failed'
					);
				}
			}
		);
	});
});