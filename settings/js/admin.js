var SharingGroupList = {
	applyMultipleSelect: function(element) {
		var checked = [];
		if ($(element).hasClass('groupsselect')) {
			if (element.data('userGroups')) {
				checked = element.data('userGroups');
			}
			var checkHandeler = function(group) {
					$.post(OC.filePath('settings', 'ajax', 'excludegroups.php'),
						{changedGroup: group, selectedGroups: JSON.stringify(checked)},
						function() {});
				};


			var addGroup = function(select, group) {
				$(this).each(function(index, element) {
					if ($(element).find('option[value="' + group + '"]').length === 0 &&
							select.data('msid') !== $(element).data('msid')) {
						$(element).append('<option value="' + escapeHTML(group) + '">' +
								escapeHTML(group) + '</option>');
					}
				});
			};

			var label = null;
			element.multiSelect({
				createCallback: addGroup,
				createText: label,
				selectedFirst: true,
				checked: checked,
				oncheck: checkHandeler,
				onuncheck: checkHandeler,
				minWidth: 100
			});

		}
	}
};

$(document).ready(function(){

	$('select#excludedGroups[multiple]').each(function (index, element) {
		SharingGroupList.applyMultipleSelect($(element));
	});


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
		$('#shareAPI p:not(#enable)').toggleClass('hidden', !this.checked);
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

	$('#shareapiDefaultExpireDate').change(function() {
		$("#setDefaultExpireDate").toggleClass('hidden', !this.checked);
	});

	$('#allowLinks').change(function() {
		$("#publicLinkSettings").toggleClass('hidden', !this.checked);
		$('#setDefaultExpireDate').toggleClass('hidden', !(this.checked && $('#shareapiDefaultExpireDate')[0].checked));
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
			OC.msg.finishedSaving('#mail_settings_msg', data);
		});
	});

	$('#sendtestemail').click(function(event){
		event.preventDefault();
		OC.msg.startAction('#sendtestmail_msg', t('settings', 'Sending...'));
		$.post(OC.generateUrl('/settings/admin/mailtest'), '', function(data){
			OC.msg.finishedAction('#sendtestmail_msg', data);
		});
	});

	$('#shareapiExcludeGroups').change(function() {
		$("#selectExcludedGroups").toggleClass('hidden', !this.checked);
	});
});
