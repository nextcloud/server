$(document).ready(function() {

	$('#fileSharingSettings input').change(function() {
		var value = 'no';
		if (this.checked) {
			value = 'yes';
		}
		OC.AppConfig.setValue('files_sharing', $(this).attr('name'), value);
	});

});
