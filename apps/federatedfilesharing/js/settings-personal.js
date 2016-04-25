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
		$('#oca-files-sharing-add-to-your-website-expanded').slideDown();
	});

});
