$(document).ready(function(){
	$('#loglevel').change(function(){
		$.post(OC.filePath('settings','ajax','setloglevel.php'), { level: $(this).val() },function(){
			OC.Log.reload();
		} );
	});

	$('#backgroundjobs input').change(function(){
		if($(this).attr('checked')){
			var mode = $(this).val();
			if (mode == 'ajax' || mode == 'webcron' || mode == 'cron') {
				OC.AppConfig.setValue('core', 'backgroundjobs_mode', mode);
			}
		}
	});

	$('#shareAPIEnabled').change(function() {
		$('.shareAPI td:not(#enable)').toggle();
	});

	$('#shareAPI input').change(function() {
		if ($(this).attr('type') == 'radio') {
			console.log('radio');
		} 
		if ($(this).attr('type') == 'checkbox') {
			console.log('checked');
		}
		OC.AppConfig.setValue('core', 'shareapi_', $(this).val());
	});
});