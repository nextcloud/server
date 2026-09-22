<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		:name="t('sharebymail', 'Share by mail')"
		:description="t('sharebymail', 'Allows people to share a personalized link to a file or folder by putting in an email address.')">
		<NcCheckboxRadioSwitch v-model="sendPasswordMail" type="switch">
			{{ t('sharebymail', 'Send password by mail') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch v-model="replyToInitiator" type="switch">
			{{ t('sharebymail', 'Reply to initiator') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch v-model="useUserEmail" type="switch">
			{{ t('sharebymail', 'Send share emails from user\'s email address') }}
		</NcCheckboxRadioSwitch>
		<p v-if="useUserEmail" class="settings-hint">
			{{ t('sharebymail', 'When enabled, share notification emails will be sent from the user\'s personal email address via their Mail Provider (e.g. Nextcloud Mail), similar to calendar invitations. Falls back to the system email if no Mail Provider is available.') }}
		</p>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { logger } from '../logger.ts'

export default {
	name: 'AdminSettings',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
	},

	setup() {
		return { t }
	},

	data() {
		return {
			sendPasswordMail: loadState('sharebymail', 'sendPasswordMail'),
			replyToInitiator: loadState('sharebymail', 'replyToInitiator'),
			useUserEmail: loadState('sharebymail', 'useUserEmail'),
		}
	},

	watch: {
		sendPasswordMail(newValue) {
			this.update('sendpasswordmail', newValue)
		},

		replyToInitiator(newValue) {
			this.update('replyToInitiator', newValue)
		},

		useUserEmail(newValue) {
			this.update('useUserEmail', newValue)
		},
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
				logger.error(errorMessage, { error })
			}
		},
	},
}
</script>

<style scoped>
.settings-hint {
	color: var(--color-text-maxcontrast);
	margin-top: 4px;
	margin-left: 44px;
	font-size: 0.9em;
}
</style>
