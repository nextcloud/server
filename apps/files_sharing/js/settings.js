$(document).ready(function() {
	$('#allowResharing').bind('change', function() {
		var checked = 1;
		if (!$('#allowResharing').attr('checked')) {
			checked = 0;
		}
		$.post(OC.filePath('files_sharing','ajax','toggleresharing.php'), 'resharing='+checked);
	});
});