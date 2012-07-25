$(document).ready(function(){
	$('#journal_calendar').on('change', function(event){
		$.post(OC.filePath('journal', 'ajax', 'setdefaultcalendar.php'), {'id':$('#journal_calendar option:selected').val()}, function(jsondata) {
			var success = {padding: 0.5em, background-color:green, color: white, font-weight: bold, float: left};
			var failure = {padding: 0.5em, background-color:red, color: white, font-weight: bold, float: left};
			if(jsondata.status == 'success') {
				$('#journal_status');
				$('#journal_status').css(success).html(t('journal', 'Saved')).fadeIn().fadeOut(5000);
			} else {
				$('#journal_status').css(failure);
				$('#journal_status').html(t('journal', 'Error saving: ')+jsondata.data.message).fadeIn().fadeOut(5000);
			}
		});
	});
});
