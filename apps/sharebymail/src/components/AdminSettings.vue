<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('sharebymail', 'Share by mail')"
		:description="t('sharebymail', 'Allows people to share a personalized link to a file or folder by putting in an email address.')">
		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="sendPasswordMail"
			@update:checked="update('sendpasswordmail', sendPasswordMail)">
			{{ t('sharebymail', 'Send password by mail') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="replyToInitiator"
			@update:checked="update('replyToInitiator', replyToInitiator)">
			{{ t('sharebymail', 'Reply to initiator') }}
		</NcCheckboxRadioSwitch>
	</NcSettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import { generateOcsUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import axios from '@nextcloud/axios'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import '@nextcloud/password-confirmation/dist/style.css'

export default {
	name: 'AdminSettings',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
	},
	data() {
		return {
			sendPasswordMail: loadState('sharebymail', 'sendPasswordMail'),
			replyToInitiator: loadState('sharebymail', 'replyToInitiator'),
		}
	},
	methods: {
		async update(key, value) {
			await confirmPassword()
			const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
				appId: 'sharebymail',
				key,
			})
			const stringValue = value ? 'yes' : 'no'
			try {
				const { data } = await axios.post(url, {
					value: stringValue,
				})
				this.handleResponse({
					status: data.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('sharebymail', 'Unable to update share by mail config'),
					error: e,
				})
			}
		},
		async handleResponse({ status, errorMessage, error }) {
			if (status !== 'ok') {
				showError(errorMessage)
				console.error(errorMessage, error)
			}
		},
	},
}
</script>
