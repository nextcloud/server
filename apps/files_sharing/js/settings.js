$(document).ready(function() {
	$('#allowResharing').bind('change', function() {
		var checked = 1;
		if (!this.checked) {
			checked = 0;
		}
		$.post(OC.filePath('files_sharing','ajax','toggleresharing.php'), 'resharing='+checked);
	});
	$('#allowSharingWithEveryone').bind('change', function() {
		var checked = 1;
		if (!this.checked) {
			checked = 0;
		}
		$.post(OC.filePath('files_sharing','ajax','togglesharewitheveryone.php'), 'allowSharingWithEveryone='+checked);
	});
});