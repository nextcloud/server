jQuery(document).ready(function() {
	$('#submit').click(function (e) {
		$('#submit + .submit-icon')
			.removeClass('icon-confirm-white')
			.addClass(OCA.Theming && OCA.Theming.inverted
				? 'icon-loading-small'
				: 'icon-loading-small-dark');
	})
})