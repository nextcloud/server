$(document).ready(function() {

	$('#externalStorage tbody tr').each(function() {
		if ($(this).find('.backend').data('class') == 'OC_Filestorage_Google') {
			var token = $(this).find('[data-parameter="token"]');
			var token_secret = $(this).find('[data-parameter="token_secret"]');
			if ($(token).val() == '' && $(token).val() == '') {
				$(this).find('.configuration').append('<a class="button google">Grant access</a>');
			} else  {
				var params = {};
				window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
					params[key] = value;
				});
				var access = true;
				if (params['oauth_token'] !== undefined && params['oauth_verifier'] !== undefined && decodeURIComponent(params['oauth_token']) == $(token).val()) {
					var tr = $(this);
					$.post(OC.filePath('files_external', 'ajax', 'google.php'), { step: 2, oauth_verifier: params['oauth_verifier'], request_token: $(token).val(), request_token_secret: $(token_secret).val() }, function(result) {
						if (result && result.status == 'success') {
							$(token).val(result.access_token);
							$(token_secret).val(result.access_token_secret);
							OC.MountConfig.saveStorage(tr);
						} else {
							access = false;
							OC.dialogs.alert(result.data.message, 'Error configuring Google Drive storage');
						}
					});
				}
				if (access && $(this).find('.configuration #granted').length == 0) {
					$(this).find('.configuration').append('<span id="granted" style="padding-left:0.5em;">Access granted</span>');
				}
			}
		}
	});

	$('.google').live('click', function(event) {
		event.preventDefault();
		var tr = $(this).parent().parent();
		var token = $(this).parent().find('[data-parameter="token"]');
		var token_secret = $(this).parent().find('[data-parameter="token_secret"]');
		$.post(OC.filePath('files_external', 'ajax', 'google.php'), { step: 1, callback: window.location.href }, function(result) {
			if (result && result.status == 'success') {
				$(token).val(result.data.request_token);
				$(token_secret).val(result.data.request_token_secret);
				OC.MountConfig.saveStorage(tr);
				window.location = result.data.url;
			} else {
				OC.dialogs.alert(result.data.message, 'Error configuring Google Drive storage');
			}
		});
	});

});
