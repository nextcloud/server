$(document).ready(function(){
	$('#loglevel').change(function(){
		$.post(OC.filePath('settings','ajax','setloglevel.php'), { level: $(this).val() },function(){
			OC.Log.reload();
		} );
	});

	$('#backgroundjobs input').change(function(){
		if($(this).attr('checked')){
			var mode = $(this).val();
			if (mode === 'ajax' || mode === 'webcron' || mode === 'cron') {
				OC.AppConfig.setValue('core', 'backgroundjobs_mode', mode);
			}
		}
	});

	$('#shareAPIEnabled').change(function() {
		$('.shareAPI td:not(#enable)').toggle();
	});

	$('#shareAPI input').change(function() {
		if ($(this).attr('type') === 'checkbox') {
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
		$.post(OC.filePath('settings','ajax','setsecurity.php'), { enforceHTTPS: $('#forcessl').val() },function(){} );
	});

	$('#mail_smtpauth').change(function() {
		if (!this.checked) {
			$('#mail_credentials').addClass('hidden');
		} else {
			$('#mail_credentials').removeClass('hidden');
		}
	});

	$('#mail_smtpmode').change(function() {
		if ($(this).val() !== 'smtp') {
			$('#setting_smtpauth').addClass('hidden');
			$('#setting_smtphost').addClass('hidden');
			$('#mail_smtpsecure_label').addClass('hidden');
			$('#mail_smtpsecure').addClass('hidden');
			$('#mail_credentials').addClass('hidden');
		} else {
			$('#setting_smtpauth').removeClass('hidden');
			$('#setting_smtphost').removeClass('hidden');
			$('#mail_smtpsecure_label').removeClass('hidden');
			$('#mail_smtpsecure').removeClass('hidden');
			if ($('#mail_smtpauth').attr('checked')) {
				$('#mail_credentials').removeClass('hidden');
			}
		}
	});

	$('#mail_settings').change(function(){
		OC.msg.startSaving('#mail_settings_msg');
		var post = $( "#mail_settings" ).serialize();
		$.post(OC.generateUrl('/settings/admin/mailsettings'), post, function(data){
			OC.msg.finishedSaving('#mail_settings .msg', data);
		});
	});

	$('#sendtestemail').click(function(){
		OC.msg.startAction('#sendtestmail_msg', t('settings', 'Sending...'));
		var post = $( "#sendtestemail" ).serialize();
		$.post(OC.generateUrl('/settings/admin/mailtest'), post, function(data){
			OC.msg.finishedAction('#sendtestmail_msg', data);
		});
	});
});
