$(document).ready(function() {

	function generateUrl($tr) {
		// no mapping between client ID and Google 'project', so we always load the same URL
		return 'https://console.developers.google.com/';
	}

	OCA.External.Settings.mountConfig.whenSelectBackend(function($tr, backend, onCompletion) {
		if (backend === 'googledrive') {
			var config = $tr.find('.configuration');
			var el = $(document.createElement('a'))
				.attr('href', generateUrl($tr))
				.attr('target', '_blank')
				.text(t('files_external', 'Google Drive Configuration') + ' ↗')
			;
			el.on('click', function(event) {
				var a = $(event.target);
				a.attr('href', generateUrl($(this).parent()));
			});
			config.append(el);
		}
	});

});
