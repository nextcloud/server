/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import $ from 'jquery'

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
				OC.msg.finishedError('#sendtestmail_msg', error.response.data)
			})
	})
})
