/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import $ from 'jquery'
import axios from '@nextcloud/axios'

window.addEventListener('DOMContentLoaded', () => {
	$('#loglevel').change(function() {
		$.post(generateUrl('/settings/admin/log/level'), { level: $(this).val() }, () => {
			OC.Log.reload()
		})
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
		axios.post(generateUrl('/settings/admin/mailsettings'), $('#mail_general_settings_form').serialize())
			.then(() => {
				OC.msg.finishedSuccess('#mail_settings_msg', t('settings', 'Saved'))
			}).catch((error) => {
				OC.msg.finishedError('#mail_settings_msg', error)
			})
	}

	const toggleEmailCredentials = function() {
		if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
			OC.PasswordConfirmation.requirePasswordConfirmation(toggleEmailCredentials)
			return
		}

		OC.msg.startSaving('#mail_settings_msg')
		axios.post(generateUrl('/settings/admin/mailsettings/credentials'), $('#mail_credentials_settings').serialize())
			.then(() => {
				OC.msg.finishedSuccess('#mail_settings_msg', t('settings', 'Saved'))
			}).catch((error) => {
				OC.msg.finishedError('#mail_settings_msg', error)
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

		axios.post(generateUrl('/settings/admin/mailtest'))
			.then(() => {
				OC.msg.finishedSuccess('#sendtestmail_msg', t('settings', 'Email sent'))
			}).catch((error) => {
				OC.msg.finishedError('#sendtestmail_msg', error)
			})
	})

	const setupChecks = () => {
		// run setup checks then gather error messages
		$.when(
			OC.SetupChecks.checkSetup(),
		).then((messages) => {
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
