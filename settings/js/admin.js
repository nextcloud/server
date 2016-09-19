$(document).ready(function(){
	var params = OC.Util.History.parseUrlQuery();

	// Hack to add a trusted domain
	if (params.trustDomain) {
		OC.dialogs.confirm(t('settings', 'Are you really sure you want add "{domain}" as trusted domain?',
				{domain: params.trustDomain}),
			t('settings', 'Add trusted domain'), function(answer) {
				if(answer) {
					$.ajax({
						type: 'POST',
						url: OC.generateUrl('settings/admin/security/trustedDomains'),
						data: { newTrustedDomain: params.trustDomain }
					}).done(function() {
						window.location.replace(OC.generateUrl('settings/admin'));
					});
				}
			});
	}


	$('#excludedGroups').each(function (index, element) {
		OC.Settings.setupGroupsSelect($(element));
		$(element).change(function(ev) {
			var groups = ev.val || [];
			groups = JSON.stringify(groups);
			OC.AppConfig.setValue('core', $(this).attr('name'), groups);
		});
	});


	$('#loglevel').change(function(){
		$.post(OC.generateUrl('/settings/admin/log/level'), {level: $(this).val()},function(){
			OC.Log.reload();
		} );
	});

	$('#backgroundjobs span.crondate').tooltip({placement: 'top'});

	$('#backgroundjobs input').change(function(){
		if($(this).is(':checked')){
			var mode = $(this).val();
			if (mode === 'ajax' || mode === 'webcron' || mode === 'cron') {
				OC.AppConfig.setValue('core', 'backgroundjobs_mode', mode);
				// clear cron errors on background job mode change
				OC.AppConfig.deleteKey('core', 'cronErrors');
			}
		}
	});

	$('#shareAPIEnabled').change(function() {
		$('#shareAPI p:not(#enable)').toggleClass('hidden', !this.checked);
	});

	$('#enableEncryption').change(function() {
		$('#encryptionAPI div#EncryptionWarning').toggleClass('hidden');
	});

	$('#reallyEnableEncryption').click(function() {
		$('#encryptionAPI div#EncryptionWarning').toggleClass('hidden');
		$('#encryptionAPI div#EncryptionSettingsArea').toggleClass('hidden');
		OC.AppConfig.setValue('core', 'encryption_enabled', 'yes');
		$('#enableEncryption').attr('disabled', 'disabled');
	});

	$('#startmigration').click(function(event){
		$(window).on('beforeunload.encryption', function(e) {
			return t('settings', 'Migration in progress. Please wait until the migration is finished');
		});
		event.preventDefault();
		$('#startmigration').prop('disabled', true);
		OC.msg.startAction('#startmigration_msg', t('settings', 'Migration started …'));
		$.post(OC.generateUrl('/settings/admin/startmigration'), '', function(data){
			OC.msg.finishedAction('#startmigration_msg', data);
			if (data['status'] === 'success') {
				$('#encryptionAPI div#selectEncryptionModules').toggleClass('hidden');
				$('#encryptionAPI div#migrationWarning').toggleClass('hidden');
			} else {
				$('#startmigration').prop('disabled', false);
			}
			$(window).off('beforeunload.encryption');

		});
	});

	$('#shareapiExpireAfterNDays').change(function() {
		var value = $(this).val();
		if (value <= 0) {
			$(this).val("1");
		}
	});

	$('#shareAPI input:not(.noJSAutoUpdate)').change(function() {
		var value = $(this).val();
		if ($(this).attr('type') === 'checkbox') {
			if (this.checked) {
				value = 'yes';
			} else {
				value = 'no';
			}
		}
		OC.AppConfig.setValue('core', $(this).attr('name'), value);
	});

	$('#shareapiDefaultExpireDate').change(function() {
		$("#setDefaultExpireDate").toggleClass('hidden', !this.checked);
	});

	$('#publicShareDisclaimer').change(function() {
		$("#publicShareDisclaimerText").toggleClass('hidden', !this.checked);
		if(!this.checked) {
			savePublicShareDisclaimerText('');
		}
	});

	var savePublicShareDisclaimerText = _.debounce(function(value) {
		var options = {
			success: function() {
				OC.msg.finishedSuccess('#publicShareDisclaimerStatus', t('core', 'Saved'));
			},
			error: function() {
				OC.msg.finishedError('#publicShareDisclaimerStatus', t('core', 'Not saved'));
			}
		};

		OC.msg.startSaving('#publicShareDisclaimerStatus');
		if (_.isString(value) && value !== '') {
			OCP.AppConfig.setValue('core', 'shareapi_public_link_disclaimertext', value, options);
		} else {
			$('#publicShareDisclaimerText').val('');
			OCP.AppConfig.deleteKey('core', 'shareapi_public_link_disclaimertext', options);
		}
	}, 500);

	$('#publicShareDisclaimerText').on('change, keyup', function() {
		savePublicShareDisclaimerText(this.value);
	});

	$('#allowLinks').change(function() {
		$("#publicLinkSettings").toggleClass('hidden', !this.checked);
		$('#setDefaultExpireDate').toggleClass('hidden', !(this.checked && $('#shareapiDefaultExpireDate')[0].checked));
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
			if ($('#mail_smtpauth').is(':checked')) {
				$('#mail_credentials').removeClass('hidden');
			}
		}
	});

	$('#mail_general_settings_form').change(function(){
		OC.msg.startSaving('#mail_settings_msg');
		var post = $( "#mail_general_settings_form" ).serialize();
		$.post(OC.generateUrl('/settings/admin/mailsettings'), post, function(data){
			OC.msg.finishedSaving('#mail_settings_msg', data);
		});
	});

	$('#mail_credentials_settings_submit').click(function(){
		OC.msg.startSaving('#mail_settings_msg');
		var post = $( "#mail_credentials_settings" ).serialize();
		$.post(OC.generateUrl('/settings/admin/mailsettings/credentials'), post, function(data){
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

	$('#allowGroupSharing').change(function() {
		$('#allowGroupSharing').toggleClass('hidden', !this.checked);
	});

	$('#shareapiExcludeGroups').change(function() {
		$("#selectExcludedGroups").toggleClass('hidden', !this.checked);
	});

	// run setup checks then gather error messages
	$.when(
		OC.SetupChecks.checkWebDAV(),
		OC.SetupChecks.checkWellKnownUrl('/.well-known/caldav/', oc_defaults.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === 'true'),
		OC.SetupChecks.checkWellKnownUrl('/.well-known/carddav/', oc_defaults.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === 'true'),
		OC.SetupChecks.checkSetup(),
		OC.SetupChecks.checkGeneric(),
		OC.SetupChecks.checkDataProtected()
	).then(function(check1, check2, check3, check4, check5, check6) {
		var messages = [].concat(check1, check2, check3, check4, check5, check6);
		var $el = $('#postsetupchecks');
		$el.find('.loading').addClass('hidden');

		var hasMessages = false;
		var $errorsEl = $el.find('.errors');
		var $warningsEl = $el.find('.warnings');
		var $infoEl = $el.find('.info');

		for (var i = 0; i < messages.length; i++ ) {
			switch(messages[i].type) {
				case OC.SetupChecks.MESSAGE_TYPE_INFO:
					$infoEl.append('<li>' + messages[i].msg + '</li>');
					break;
				case OC.SetupChecks.MESSAGE_TYPE_WARNING:
					$warningsEl.append('<li>' + messages[i].msg + '</li>');
					break;
				case OC.SetupChecks.MESSAGE_TYPE_ERROR:
				default:
					$errorsEl.append('<li>' + messages[i].msg + '</li>');
			}
		}

		if ($errorsEl.find('li').length > 0) {
			$errorsEl.removeClass('hidden');
			hasMessages = true;
		}
		if ($warningsEl.find('li').length > 0) {
			$warningsEl.removeClass('hidden');
			hasMessages = true;
		}
		if ($infoEl.find('li').length > 0) {
			$infoEl.removeClass('hidden');
			hasMessages = true;
		}

		if (hasMessages) {
			$el.find('.hint').removeClass('hidden');
		} else {
			var securityWarning = $('#security-warning');
			if (securityWarning.children('ul').children().length === 0) {
				$('#security-warning-state').find('span').removeClass('hidden');
			}
		}
	});
});
