window.addEventListener('DOMContentLoaded', function() {

	function displayGranted($tr) {
		$tr.find('.configuration input.auth-param').attr('disabled', 'disabled').addClass('disabled-success');
	}

	OCA.Files_External.Settings.mountConfig.whenSelectAuthMechanism(function($tr, authMechanism, scheme, onCompletion) {
		if (authMechanism === 'oauth2::oauth2') {
			var config = $tr.find('.configuration');
			config.append($(document.createElement('input'))
				.addClass('button auth-param')
				.attr('type', 'button')
				.attr('value', t('files_external', 'Grant access'))
				.attr('name', 'oauth2_grant')
			);

			onCompletion.then(function() {
				var configured = $tr.find('[data-parameter="configured"]');
				if ($(configured).val() == 'true') {
					displayGranted($tr);
				} else {
					var client_id = $tr.find('.configuration [data-parameter="client_id"]').val();
					var client_secret = $tr.find('.configuration [data-parameter="client_secret"]')
						.val();
					if (client_id != '' && client_secret != '') {
						var params = {};
						window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
							params[key] = value;
						});
						if (params['code'] !== undefined) {
							var token = $tr.find('.configuration [data-parameter="token"]');
							var statusSpan = $tr.find('.status span');
							statusSpan.removeClass();
							statusSpan.addClass('waiting');
							$.post(OC.filePath('files_external', 'ajax', 'oauth2.php'),
									{
										step: 2,
										client_id: client_id,
										client_secret: client_secret,
										redirect: location.protocol + '//' + location.host + location.pathname,
										code: params['code'],
									}, function(result) {
										if (result && result.status == 'success') {
											$(token).val(result.data.token);
											$(configured).val('true');
											OCA.Files_External.Settings.mountConfig.saveStorageConfig($tr, function(status) {
												if (status) {
													displayGranted($tr);
												}
											});
										} else {
											OC.dialogs.alert(result.data.message,
													t('files_external', 'Error configuring OAuth2')
													);
										}
									}
							);
						}
					}
				}
			});
		}
	});

	$('#externalStorage').on('click', '[name="oauth2_grant"]', function(event) {
		event.preventDefault();
		var tr = $(this).parent().parent();
		var configured = $(this).parent().find('[data-parameter="configured"]');
		var client_id = $(this).parent().find('[data-parameter="client_id"]').val();
		var client_secret = $(this).parent().find('[data-parameter="client_secret"]').val();
		if (client_id != '' && client_secret != '') {
			var token = $(this).parent().find('[data-parameter="token"]');
			$.post(OC.filePath('files_external', 'ajax', 'oauth2.php'),
				{
					step: 1,
					client_id: client_id,
					client_secret: client_secret,
					redirect: location.protocol + '//' + location.host + location.pathname,
				}, function(result) {
					if (result && result.status == 'success') {
						$(configured).val('false');
						$(token).val('false');
						OCA.Files_External.Settings.mountConfig.saveStorageConfig(tr, function(status) {
							window.location = result.data.url;
						});
					} else {
						OC.dialogs.alert(result.data.message,
							t('files_external', 'Error configuring OAuth2')
						);
					}
				}
			);
		}
	});

});
