$(document).ready(function() {

	$('#fileSharingSettings button.pop-up').click(function() {
		var url = $(this).data('url');
		if (url) {
			var width = 600;
			var height = 400;
			var left = (screen.width/2)-(width/2);
			var top = (screen.height/2)-(height/2);

			window.open(url, 'name', 'width=' + width + ', height=' + height + ', top=' + top + ', left=' + left);
		}
	});

	$('#oca-files-sharing-add-to-your-website').click(function() {
		if ($('#oca-files-sharing-add-to-your-website-expanded').is(':visible')) {
			$('#oca-files-sharing-add-to-your-website-expanded').slideUp();
		} else {
			$('#oca-files-sharing-add-to-your-website-expanded').slideDown();
		}
	});

	/* Verification icon tooltip */
	$('#personal-settings-container .verify img').tooltip({placement: 'bottom', trigger: 'hover'});

	$('#fileSharingSettings .clipboardButton').tooltip({placement: 'bottom', title: t('core', 'Copy'), trigger: 'hover'});

	// Clipboard!
	var clipboard = new Clipboard('.clipboardButton');
	clipboard.on('success', function(e) {
		var $input = $(e.trigger);
		$input.tooltip('hide')
			.attr('data-original-title', t('core', 'Copied!'))
			.tooltip('fixTitle')
			.tooltip({placement: 'bottom', trigger: 'manual'})
			.tooltip('show');
		_.delay(function() {
			$input.tooltip('hide')
				.attr('data-original-title', t('core', 'Copy'))
				.tooltip('fixTitle');
		}, 3000);
	});
	clipboard.on('error', function (e) {
		var $input = $(e.trigger);
		var actionMsg = '';
		if (/iPhone|iPad/i.test(navigator.userAgent)) {
			actionMsg = t('core', 'Not supported!');
		} else if (/Mac/i.test(navigator.userAgent)) {
			actionMsg = t('core', 'Press ⌘-C to copy.');
		} else {
			actionMsg = t('core', 'Press Ctrl-C to copy.');
		}

		$input.tooltip('hide')
			.attr('data-original-title', actionMsg)
			.tooltip('fixTitle')
			.tooltip({placement: 'bottom', trigger: 'manual'})
			.tooltip('show');
		_.delay(function () {
			$input.tooltip('hide')
				.attr('data-original-title', t('core', 'Copy'))
				.tooltip('fixTitle');
		}, 3000);
	});


	$('#fileSharingSettings .hasTooltip').tooltip({placement: 'right'});
});
