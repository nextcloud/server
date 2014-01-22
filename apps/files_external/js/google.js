$(document).ready(function() {

	$('#externalStorage tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google').each(function() {
		var configured = $(this).find('[data-parameter="configured"]');
		if ($(configured).val() == 'true') {
			$(this).find('.configuration input').attr('disabled', 'disabled');
			$(this).find('.configuration').append($('<span/>').attr('id', 'access')
				.text(t('files_external', 'Access granted')));
		} else {
			var client_id = $(this).find('.configuration [data-parameter="client_id"]').val();
			var client_secret = $(this).find('.configuration [data-parameter="client_secret"]')
				.val();
			if (client_id != '' && client_secret != '') {
				var params = {};
				window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
					params[key] = value;
				});
				if (params['code'] !== undefined) {
					var tr = $(this);
					var token = $(this).find('.configuration [data-parameter="token"]');
					var statusSpan = $(tr).find('.status span');
					statusSpan.removeClass();
					statusSpan.addClass('waiting');
					$.post(OC.filePath('files_external', 'ajax', 'google.php'),
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
								OC.MountConfig.saveStorage(tr, function(status) {
									if (status) {
										$(tr).find('.configuration input').attr('disabled', 'disabled');
										$(tr).find('.configuration').append($('<span/>')
											.attr('id', 'access')
											.text(t('files_external', 'Access granted')));
									}
								});
							} else {
								OC.dialogs.alert(result.data.message,
									t('files_external', 'Error configuring Google Drive storage')
								);
							}
						}
					);
				}
			} else {
				onGoogleInputsChange($(this));
			}
		}
	});

	$('#externalStorage').on('paste', 'tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google td',
		function() {
			var tr = $(this).parent();
			setTimeout(function() {
				onGoogleInputsChange(tr);
			}, 20);
		}
	);

	$('#externalStorage').on('keyup', 'tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google td',
		function() {
			onGoogleInputsChange($(this).parent());
		}
	);

	$('#externalStorage').on('change', 'tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google .chzn-select'
		, function() {
			onGoogleInputsChange($(this).parent().parent());
		}
	);

	function onGoogleInputsChange(tr) {
		if ($(tr).find('[data-parameter="configured"]').val() != 'true') {
			var config = $(tr).find('.configuration');
			if ($(tr).find('.mountPoint input').val() != ''
				&& $(config).find('[data-parameter="client_id"]').val() != ''
				&& $(config).find('[data-parameter="client_secret"]').val() != ''
				&& ($(tr).find('.chzn-select').length == 0
				|| $(tr).find('.chzn-select').val() != null))
			{
				if ($(tr).find('.google').length == 0) {
					$(config).append($('<a/>').addClass('button google')
						.text(t('files_external', 'Grant access')));
				} else {
					$(tr).find('.google').show();
				}
			} else if ($(tr).find('.google').length > 0) {
				$(tr).find('.google').hide();
			}
		}
	}

	$('#externalStorage').on('click', '.google', function(event) {
		event.preventDefault();
		var tr = $(this).parent().parent();
		var configured = $(this).parent().find('[data-parameter="configured"]');
		var client_id = $(this).parent().find('[data-parameter="client_id"]').val();
		var client_secret = $(this).parent().find('[data-parameter="client_secret"]').val();
		if (client_id != '' && client_secret != '') {
			var token = $(this).parent().find('[data-parameter="token"]');
			$.post(OC.filePath('files_external', 'ajax', 'google.php'),
				{
					step: 1,
					client_id: client_id,
					client_secret: client_secret,
					redirect: location.protocol + '//' + location.host + location.pathname,
				}, function(result) {
					if (result && result.status == 'success') {
						$(configured).val('false');
						$(token).val('false');
						OC.MountConfig.saveStorage(tr, function(status) {
							window.location = result.data.url;
						});
					} else {
						OC.dialogs.alert(result.data.message,
							t('files_external', 'Error configuring Google Drive storage')
						);
					}
				}
			);
		}
	});

});
