window.addEventListener('DOMContentLoaded', () => {
	$('#excludedGroups,#linksExcludedGroups,#passwordsExcludedGroups').each(function(index, element) {
		OC.Settings.setupGroupsSelect($(element))
		$(element).change((ev) => {
			let groups = ev.val || []
			groups = JSON.stringify(groups)
			OCP.AppConfig.setValue('core', $(this).attr('name'), groups)
		})
	})

	$('#loglevel').change(() => {
		$.post(OC.generateUrl('/settings/admin/log/level'), { level: $(this).val() }, () => {
			OC.Log.reload()
		})
	})

	$('#backgroundjobs span.crondate').tooltip({ placement: 'top' })

	$('#backgroundjobs input').change(() => {
		if ($(this).is(':checked')) {
			const mode = $(this).val()
			if (mode === 'ajax' || mode === 'webcron' || mode === 'cron') {
				OCP.AppConfig.setValue('core', 'backgroundjobs_mode', mode, {
					success: () => {
						// clear cron errors on background job mode change
						OCP.AppConfig.deleteKey('core', 'cronErrors')
					}
				})
			}
		}
	})

	$('#shareAPIEnabled').change(() => {
		$('#shareAPI p:not(#enable)').toggleClass('hidden', !this.checked)
	})

	$('#enableEncryption').change(() => {
		$('#encryptionAPI div#EncryptionWarning').toggleClass('hidden')
	})

	$('#reallyEnableEncryption').click(() => {
		$('#encryptionAPI div#EncryptionWarning').toggleClass('hidden')
		$('#encryptionAPI div#EncryptionSettingsArea').toggleClass('hidden')
		OCP.AppConfig.setValue('core', 'encryption_enabled', 'yes')
		$('#enableEncryption').attr('disabled', 'disabled')
	})

	$('#startmigration').click((event) => {
		$(window).on('beforeunload.encryption', (e) => {
			return t('settings', 'Migration in progress. Please wait until the migration is finished')
		})
		event.preventDefault()
		$('#startmigration').prop('disabled', true)
		OC.msg.startAction('#startmigration_msg', t('settings', 'Migration started …'))
		$.post(OC.generateUrl('/settings/admin/startmigration'), '', function(data) {
			OC.msg.finishedAction('#startmigration_msg', data)
			if (data.status === 'success') {
				$('#encryptionAPI div#selectEncryptionModules').toggleClass('hidden')
				$('#encryptionAPI div#migrationWarning').toggleClass('hidden')
			} else {
				$('#startmigration').prop('disabled', false)
			}
			$(window).off('beforeunload.encryption')

		})
	})

	$('#shareapiExpireAfterNDays').on('input', function() {
		this.value = this.value.replace(/\D/g, '')
	})

	$('#shareAPI input:not(.noJSAutoUpdate)').change(function() {
		let value = $(this).val()
		if ($(this).attr('type') === 'checkbox') {
			if (this.checked) {
				value = 'yes'
			} else {
				value = 'no'
			}
		}
		OCP.AppConfig.setValue('core', $(this).attr('name'), value)
	})

	$('#shareapiDefaultExpireDate').change(function() {
		$('setDefaultExpireDate').toggleClass('hidden', !this.checked)
	})

	$('#shareapiDefaultInternalExpireDate').change(function() {
		$('#setDefaultInternalExpireDate').toggleClass('hidden', !this.checked)
	})

	$('#shareapiDefaultRemoteExpireDate').change(function() {
		$('#setDefaultRemoteExpireDate').toggleClass('hidden', !this.checked)
	})

	$('#enforceLinkPassword').change(function() {
		$('#selectPasswordsExcludedGroups').toggleClass('hidden', !this.checked)
	})

	$('#publicShareDisclaimer').change(function() {
		$('#publicShareDisclaimerText').toggleClass('hidden', !this.checked)
		if (!this.checked) {
			savePublicShareDisclaimerText('')
		}
	})

	$('#shareApiDefaultPermissionsSection input').change(function(ev) {
		const $el = $('#shareApiDefaultPermissions')
		const $target = $(ev.target)

		let value = $el.val()
		if ($target.is(':checked')) {
			value = value | $target.val()
		} else {
			value = value & ~$target.val()
		}

		// always set read permission
		value |= OC.PERMISSION_READ

		// this will trigger the field's change event and will save it
		$el.val(value).change()

		ev.preventDefault()

		return false
	})

	const savePublicShareDisclaimerText = _.debounce(function(value) {
		const options = {
			success: () => {
				OC.msg.finishedSuccess('#publicShareDisclaimerStatus', t('settings', 'Saved'))
			},
			error: () => {
				OC.msg.finishedError('#publicShareDisclaimerStatus', t('settings', 'Not saved'))
			}
		}

		OC.msg.startSaving('#publicShareDisclaimerStatus')
		if (_.isString(value) && value !== '') {
			OCP.AppConfig.setValue('core', 'shareapi_public_link_disclaimertext', value, options)
		} else {
			$('#publicShareDisclaimerText').val('')
			OCP.AppConfig.deleteKey('core', 'shareapi_public_link_disclaimertext', options)
		}
	}, 500)

	$('#publicShareDisclaimerText').on('change, keyup', function() {
		savePublicShareDisclaimerText(this.value)
	})

	$('#shareapi_allow_share_dialog_user_enumeration').on('change', function() {
		$('#shareapi_restrict_user_enumeration_to_group_setting').toggleClass('hidden', !this.checked)
		$('#shareapi_restrict_user_enumeration_to_phone_setting').toggleClass('hidden', !this.checked)
		$('#shareapi_restrict_user_enumeration_combinewarning_setting').toggleClass('hidden', !this.checked)
	})

	$('#shareapi_restrict_user_enumeration_full_match').on('change', function() {
		$('#shareapi_restrict_user_enumeration_full_match_userid_setting').toggleClass('hidden', !this.checked)
		$('#shareapi_restrict_user_enumeration_full_match_ignore_second_display_name_setting').toggleClass('hidden', !this.checked)
	})

	$('#allowLinks').change(function() {
		$('#publicLinkSettings').toggleClass('hidden', !this.checked)
		$('#setDefaultExpireDate').toggleClass('hidden', !(this.checked && $('#shareapiDefaultExpireDate')[0].checked))
	})

	$('#mail_smtpauth').change(function() {
		if (!this.checked) {
			$('#mail_credentials').addClass('hidden')
		} else {
			$('#mail_credentials').removeClass('hidden')
		}
	})

	$('#mail_smtpmode').change(function() {
		if ($(this).val() !== 'smtp') {
			$('#setting_smtpauth').addClass('hidden')
			$('#setting_smtphost').addClass('hidden')
			$('#mail_smtpsecure_label').addClass('hidden')
			$('#mail_smtpsecure').addClass('hidden')
			$('#mail_credentials').addClass('hidden')
			$('#mail_sendmailmode_label, #mail_sendmailmode').removeClass('hidden')
		} else {
			$('#setting_smtpauth').removeClass('hidden')
			$('#setting_smtphost').removeClass('hidden')
			$('#mail_smtpsecure_label').removeClass('hidden')
			$('#mail_smtpsecure').removeClass('hidden')
			if ($('#mail_smtpauth').is(':checked')) {
				$('#mail_credentials').removeClass('hidden')
			}
			$('#mail_sendmailmode_label, #mail_sendmailmode').addClass('hidden')
		}
	})

	const changeEmailSettings = function() {
		if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
			OC.PasswordConfirmation.requirePasswordConfirmation(changeEmailSettings)
			return
		}

		OC.msg.startSaving('#mail_settings_msg')
		$.ajax({
			url: OC.generateUrl('/settings/admin/mailsettings'),
			type: 'POST',
			data: $('#mail_general_settings_form').serialize(),
			success: () => {
				OC.msg.finishedSuccess('#mail_settings_msg', t('settings', 'Saved'))
			},
			error: (xhr) => {
				OC.msg.finishedError('#mail_settings_msg', xhr.responseJSON)
			}
		})
	}

	const toggleEmailCredentials = function() {
		if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
			OC.PasswordConfirmation.requirePasswordConfirmation(toggleEmailCredentials)
			return
		}

		OC.msg.startSaving('#mail_settings_msg')
		$.ajax({
			url: OC.generateUrl('/settings/admin/mailsettings/credentials'),
			type: 'POST',
			data: $('#mail_credentials_settings').serialize(),
			success: () => {
				OC.msg.finishedSuccess('#mail_settings_msg', t('settings', 'Saved'))
			},
			error: (xhr) => {
				OC.msg.finishedError('#mail_settings_msg', xhr.responseJSON)
			}
		})
	}

	$('#mail_general_settings_form').change(changeEmailSettings)
	$('#mail_credentials_settings_submit').click(toggleEmailCredentials)
	$('#mail_smtppassword').click(() => {
		if (this.type === 'text' && this.value === '********') {
			this.type = 'password'
			this.value = ''
		}
	})

	$('#sendtestemail').click((event) => {
		event.preventDefault()
		OC.msg.startAction('#sendtestmail_msg', t('settings', 'Sending…'))

		$.ajax({
			url: OC.generateUrl('/settings/admin/mailtest'),
			type: 'POST',
			success: () => {
				OC.msg.finishedSuccess('#sendtestmail_msg', t('settings', 'Email sent'))
			},
			error: (xhr) => {
				OC.msg.finishedError('#sendtestmail_msg', xhr.responseJSON)
			}
		})
	})

	$('#allowGroupSharing').change(() => {
		$('#allowGroupSharing').toggleClass('hidden', !this.checked)
	})

	$('#shareapiExcludeGroups').change(() => {
		$('#selectExcludedGroups').toggleClass('hidden', !this.checked)
	})

	const setupChecks = () => {
		// run setup checks then gather error messages
		$.when(
			OC.SetupChecks.checkWebDAV(),
			OC.SetupChecks.checkWellKnownUrl('GET', '/.well-known/webfinger', OC.theme.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === true, [200, 404], true),
			OC.SetupChecks.checkWellKnownUrl('GET', '/.well-known/nodeinfo', OC.theme.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === true, [200, 404], true),
			OC.SetupChecks.checkWellKnownUrl('PROPFIND', '/.well-known/caldav', OC.theme.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === true),
			OC.SetupChecks.checkWellKnownUrl('PROPFIND', '/.well-known/carddav', OC.theme.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === true),
			OC.SetupChecks.checkProviderUrl(OC.getRootPath() + '/ocm-provider/', OC.theme.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === true),
			OC.SetupChecks.checkProviderUrl(OC.getRootPath() + '/ocs-provider/', OC.theme.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === true),
			OC.SetupChecks.checkSetup(),
			OC.SetupChecks.checkGeneric(),
			OC.SetupChecks.checkWOFF2Loading(OC.filePath('core', '', 'fonts/NotoSans-Regular-latin.woff2'), OC.theme.docPlaceholderUrl),
			OC.SetupChecks.checkDataProtected()
		).then((check1, check2, check3, check4, check5, check6, check7, check8, check9, check10, check11) => {
			const messages = [].concat(check1, check2, check3, check4, check5, check6, check7, check8, check9, check10, check11)
			const $el = $('#postsetupchecks')
			$('#security-warning-state-loading').addClass('hidden')

			let hasMessages = false
			const $errorsEl = $el.find('.errors')
			const $warningsEl = $el.find('.warnings')
			const $infoEl = $el.find('.info')

			for (let i = 0; i < messages.length; i++) {
				switch (messages[i].type) {
				case OC.SetupChecks.MESSAGE_TYPE_INFO:
					$infoEl.append('<li>' + messages[i].msg + '</li>')
					break
				case OC.SetupChecks.MESSAGE_TYPE_WARNING:
					$warningsEl.append('<li>' + messages[i].msg + '</li>')
					break
				case OC.SetupChecks.MESSAGE_TYPE_ERROR:
				default:
					$errorsEl.append('<li>' + messages[i].msg + '</li>')
				}
			}

			if ($errorsEl.find('li').length > 0) {
				$errorsEl.removeClass('hidden')
				hasMessages = true
			}
			if ($warningsEl.find('li').length > 0) {
				$warningsEl.removeClass('hidden')
				hasMessages = true
			}
			if ($infoEl.find('li').length > 0) {
				$infoEl.removeClass('hidden')
				hasMessages = true
			}

			if (hasMessages) {
				$('#postsetupchecks-hint').removeClass('hidden')
				if ($errorsEl.find('li').length > 0) {
					$('#security-warning-state-failure').removeClass('hidden')
				} else {
					$('#security-warning-state-warning').removeClass('hidden')
				}
			} else {
				const securityWarning = $('#security-warning')
				if (securityWarning.children('ul').children().length === 0) {
					$('#security-warning-state-ok').removeClass('hidden')
				} else {
					$('#security-warning-state-failure').removeClass('hidden')
				}
			}
		})
	}

	if (document.getElementById('security-warning') !== null) {
		setupChecks()
	}
})
