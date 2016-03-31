$(document).ready(function() {

	function generateUrl($tr) {
		// no mapping between client ID and Google 'project', so we always load the same URL
		return 'https://console.developers.google.com/';
	}

	OCA.External.Settings.mountConfig.whenSelectBackend(function($tr, backend, onCompletion) {
		if (backend === 'googledrive') {
			var backendEl = $tr.find('.backend');
			var el = $(document.createElement('a'))
				.attr('href', generateUrl($tr))
				.attr('target', '_blank')
				.attr('title', t('files_external', 'Google Drive App Configuration'))
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
