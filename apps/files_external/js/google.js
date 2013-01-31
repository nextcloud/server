$(document).ready(function() {

	$('#externalStorage tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google').each(function() {
		var configured = $(this).find('[data-parameter="configured"]');
		if ($(configured).val() == 'true') {
			$(this).find('.configuration')
                .append('<span id="access" style="padding-left:0.5em;">'+t('files_external', 'Access granted')+'</span>');
		} else {
			var token = $(this).find('[data-parameter="token"]');
			var token_secret = $(this).find('[data-parameter="token_secret"]');
			var params = {};
			window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
				params[key] = value;
			});
			if (params['oauth_token'] !== undefined && params['oauth_verifier'] !== undefined && decodeURIComponent(params['oauth_token']) == $(token).val()) {
				var tr = $(this);
				$.post(OC.filePath('files_external', 'ajax', 'google.php'), { step: 2, oauth_verifier: params['oauth_verifier'], request_token: $(token).val(), request_token_secret: $(token_secret).val() }, function(result) {
					if (result && result.status == 'success') {
						$(token).val(result.access_token);
						$(token_secret).val(result.access_token_secret);
						$(configured).val('true');
						OC.MountConfig.saveStorage(tr);
						$(tr).find('.configuration').append('<span id="access" style="padding-left:0.5em;">'+t('files_external', 'Access granted')+'</span>');
					} else {
						OC.dialogs.alert(result.data.message,
                            t('files_external', 'Error configuring Google Drive storage')
                        );
					}
				});
			} else if ($(this).find('.google').length == 0) {
				$(this).find('.configuration').append('<a class="button google">'+t('files_external', 'Grant access')+'</a>');
			}
		}
	});

	$('#externalStorage tbody tr').live('change', function() {
		console.log('hello');
		if ($(this).hasClass('\\\\OC\\\\Files\\\\Storage\\\\Google') && $(this).find('[data-parameter="configured"]').val() != 'true') {
			if ($(this).find('.mountPoint input').val() != '') {
				if ($(this).find('.google').length == 0) {
					$(this).find('.configuration').append('<a class="button google">'+t('files_external', 'Grant access')+'</a>');
				}
			}
		}
	});

	$('#externalStorage tbody tr .mountPoint input').live('keyup', function() {
		var tr = $(this).parent().parent();
		if ($(tr).hasClass('\\\\OC\\\\Files\\\\Storage\\\\Google') && $(tr).find('[data-parameter="configured"]').val() != 'true' && $(tr).find('.google').length > 0) {
			if ($(this).val() != '') {
				$(tr).find('.google').show();
			} else {
				$(tr).find('.google').hide();
			}
		}
	});

	$('.google').live('click', function(event) {
		event.preventDefault();
		var tr = $(this).parent().parent();
		var configured = $(this).parent().find('[data-parameter="configured"]');
		var token = $(this).parent().find('[data-parameter="token"]');
		var token_secret = $(this).parent().find('[data-parameter="token_secret"]');
		$.post(OC.filePath('files_external', 'ajax', 'google.php'), { step: 1, callback: window.location.href }, function(result) {
			if (result && result.status == 'success') {
				$(configured).val('false');
				$(token).val(result.data.request_token);
				$(token_secret).val(result.data.request_token_secret);
				if (OC.MountConfig.saveStorage(tr)) {
					window.location = result.data.url;
				} else {
					OC.dialogs.alert(
                        t('files_external', 'Fill out all required fields'),
                        t('files_external', 'Error configuring Google Drive storage')
                    );
				}
			} else {
				OC.dialogs.alert(result.data.message,
                    t('files_external', 'Error configuring Google Drive storage')
                );
			}
		});
	});

});
