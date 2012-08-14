$(document).ready(function(){
	$('#loglevel').change(function(){
		$.post(OC.filePath('settings','ajax','setloglevel.php'), { level: $(this).val() },function(){
			OC.Log.reload();
		} );
	});

	$('#backgroundjobs input').change(function(){
		if($(this).attr('checked')){
			$.post(OC.filePath('settings','ajax','setbackgroundjobsmode.php'), { mode: $(this).val() });
		}
	});
});