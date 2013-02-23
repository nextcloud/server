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
		if ($(this).attr('type') == 'checkbox') {
			if (this.checked) {
				var value = 'yes';
			} else {
				var value = 'no';
			}
		} else {
			var value = $(this).val();
		}
		OC.AppConfig.setValue('core', $(this).attr('name'), value);
	});

	$('#security').change(function(){
		$.post(OC.filePath('settings','ajax','setsecurity.php'), { enforceHTTPS: $('#enforceHTTPSEnabled').val() },function(){} );
	});
});
