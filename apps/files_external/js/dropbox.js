$(document).ready(function() {

	function generateUrl($tr) {
		var app_key = $tr.find('[data-parameter="app_key"]').val();
		if (app_key) {
			return 'https://www.dropbox.com/developers/apps/info/' + app_key;
		} else {
			return 'https://www.dropbox.com/developers/apps';
		}
	}

	OCA.External.Settings.mountConfig.whenSelectBackend(function($tr, backend, onCompletion) {
		if (backend === 'dropbox') {
			var backendEl = $tr.find('.backend');
			var el = $(document.createElement('a'))
				.attr('href', generateUrl($tr))
				.attr('target', '_blank')
				.attr('title', t('files_external', 'Dropbox App Configuration'))
				.addClass('icon-settings svg')
			;
			el.on('click', function(event) {
				var a = $(event.target);
				a.attr('href', generateUrl($(this).closest('tr')));
			});
			el.tooltip({placement: 'top'});
			backendEl.append(el);
		}
	});

});
