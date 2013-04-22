$(document).ready(function() {

	$('#externalStorage tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google').each(function(index, tr) {
		setupGoogleRow(tr);
	});

	$('#externalStorage').on('change', '#selectBackend', function() {
		if ($(this).val() == '\\OC\\Files\\Storage\\Google') {
			setupGoogleRow($('#externalStorage tbody>tr:last').prev('tr'));
		}
	});

	function setupGoogleRow(tr) {
		var configured = $(tr).find('[data-parameter="configured"]');
		if ($(configured).val() == 'true') {
			$(tr).find('.configuration').append('<span id="access" style="padding-left:0.5em;">'+t('files_external', 'Access granted')+'</span>');
		} else {
			var token = $(tr).find('[data-parameter="token"]');
			var token_secret = $(tr).find('[data-parameter="token_secret"]');
			var params = {};
			window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
				params[key] = value;
			});
			if (params['oauth_token'] !== undefined && params['oauth_verifier'] !== undefined && decodeURIComponent(params['oauth_token']) == $(token).val()) {
				var statusSpan = $(tr).find('.status span');
				statusSpan.removeClass();
				statusSpan.addClass('waiting');
				$.post(OC.filePath('files_external', 'ajax', 'google.php'), { step: 2, oauth_verifier: params['oauth_verifier'], request_token: $(token).val(), request_token_secret: $(token_secret).val() }, function(result) {
					if (result && result.status == 'success') {
						$(token).val(result.access_token);
						$(token_secret).val(result.access_token_secret);
						$(configured).val('true');
						OC.MountConfig.saveStorage(tr);
						$(tr).find('.configuration').append('<span id="access" style="padding-left:0.5em;">'+t('files_external', 'Access granted')+'</span>');
					} else {
						OC.dialogs.alert(result.data.message, t('files_external', 'Error configuring Google Drive storage'));
						onGoogleInputsChange(tr);
					}
				});
			} else {
				onGoogleInputsChange(tr);
			}
		}
	}

	$('#externalStorage').on('paste', 'tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google td', function() {
		var tr = $(this).parent();
		setTimeout(function() {
			onGoogleInputsChange(tr);
		}, 20);
	});

	$('#externalStorage').on('keyup', 'tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google td', function() {
		onGoogleInputsChange($(this).parent());
	});

	$('#externalStorage').on('change', 'tbody tr.\\\\OC\\\\Files\\\\Storage\\\\Google .chzn-select', function() {
		onGoogleInputsChange($(this).parent().parent());
	});

	function onGoogleInputsChange(tr) {
		if ($(tr).find('[data-parameter="configured"]').val() != 'true') {
			var config = $(tr).find('.configuration');
			if ($(tr).find('.mountPoint input').val() != '' && ($(tr).find('.chzn-select').length == 0 || $(tr).find('.chzn-select').val() != null)) {
				if ($(tr).find('.google').length == 0) {
					$(config).append('<a class="button google">'+t('files_external', 'Grant access')+'</a>');
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
		var token = $(this).parent().find('[data-parameter="token"]');
		var token_secret = $(this).parent().find('[data-parameter="token_secret"]');
		var statusSpan = $(tr).find('.status span');
		$.post(OC.filePath('files_external', 'ajax', 'google.php'), { step: 1, callback: location.protocol + '//' + location.host + location.pathname }, function(result) {
			if (result && result.status == 'success') {
				$(configured).val('false');
				$(token).val(result.data.request_token);
				$(token_secret).val(result.data.request_token_secret);
				OC.MountConfig.saveStorage(tr);
				statusSpan.removeClass();
				statusSpan.addClass('waiting');
				window.location = result.data.url;
			} else {
				OC.dialogs.alert(result.data.message, t('files_external', 'Error configuring Google Drive storage'));
			}
		});
	});

});
