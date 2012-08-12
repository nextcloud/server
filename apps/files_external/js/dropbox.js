$(document).ready(function() {

	$('#externalStorage tbody tr').each(function() {
		if ($(this).find('.backend').data('class') == 'OC_Filestorage_Dropbox') {
			var app_key = $(this).find('.configuration [data-parameter="app_key"]').val();
			var app_secret = $(this).find('.configuration [data-parameter="app_secret"]').val();
			if (app_key == '' && app_secret == '') {
				$(this).find('.configuration').append('<a class="button dropbox">Grant access</a>');
			} else  {
				var pos = window.location.search.indexOf('oauth_token') + 12
				var token = $(this).find('.configuration [data-parameter="token"]');
				if (pos != -1 && window.location.search.substr(pos, $(token).val().length) == $(token).val()) {
					var token_secret = $(this).find('.configuration [data-parameter="token_secret"]');
					var tr = $(this);
					$.post(OC.filePath('files_external', 'ajax', 'dropbox.php'), { step: 2, app_key: app_key, app_secret: app_secret, request_token: $(token).val(), request_token_secret: $(token_secret).val() }, function(result) {
						if (result && result.status == 'success') {
							$(token).val(result.access_token);
							$(token_secret).val(result.access_token_secret);
							OC.MountConfig.saveStorage(tr);
						} else {
							OC.dialogs.alert(result.data.message, 'Error configuring Dropbox storage');
						}
					});
				} else if ($(this).find('.configuration #granted').length == 0) {
					$(this).find('.configuration').append('<span id="granted">Access granted</span>');
				}
			}
		}
	});

	$('.dropbox').live('click', function(event) {
		event.preventDefault();
		var app_key = $(this).parent().find('[data-parameter="app_key"]').val();
		var app_secret = $(this).parent().find('[data-parameter="app_secret"]').val();
		if (app_key != '' && app_secret != '') {
			var tr = $(this).parent().parent();
			var token = $(this).parent().find('[data-parameter="token"]');
			var token_secret = $(this).parent().find('[data-parameter="token_secret"]');
			$.post(OC.filePath('files_external', 'ajax', 'dropbox.php'), { step: 1, app_key: app_key, app_secret: app_secret, callback: window.location.href }, function(result) {
				if (result && result.status == 'success') {
					$(token).val(result.data.request_token);
					$(token_secret).val(result.data.request_token_secret);
					OC.MountConfig.saveStorage(tr);
					window.location = result.data.url;
				} else {
					OC.dialogs.alert(result.data.message, 'Error configuring Dropbox storage');
				}
			});
		} else {
			OC.dialogs.alert('Please provide a valid Dropbox app key and secret.', 'Error configuring Dropbox storage')
		}
	});

});
