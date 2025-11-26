<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection :name="t('settings', 'Password')">
		<form id="passwordform" method="POST" @submit.prevent="changePassword">
			<NcPasswordField
				id="old-pass"
				v-model="oldPass"
				:label="t('settings', 'Current password')"
				name="oldpassword"
				autocomplete="current-password"
				autocapitalize="none"
				spellcheck="false" />

			<NcPasswordField
				id="new-pass"
				v-model="newPass"
				:label="t('settings', 'New password')"
				:maxlength="469"
				autocomplete="new-password"
				autocapitalize="none"
				spellcheck="false"
				:check-password-strength="true" />

			<NcButton
				variant="primary"
				type="submit"
				:disabled="newPass.length === 0 || oldPass.length === 0">
				{{ t('settings', 'Change password') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

export default {
	name: 'PasswordSection',
	components: {
		NcSettingsSection,
		NcButton,
		NcPasswordField,
	},

	data() {
		return {
			oldPass: '',
			newPass: '',
		}
	},

	methods: {
		changePassword() {
			axios.post(generateUrl('/settings/personal/changepassword'), {
				oldpassword: this.oldPass,
				newpassword: this.newPass,
			})
				.then((res) => res.data)
				.then((data) => {
					if (data.status === 'error') {
						this.errorMessage = data.data.message
						showError(data.data.message)
					} else {
						showSuccess(data.data.message)
					}
				})
		},
	},
}
</script>

<style>
	#passwordform {
		display: flex;
		flex-direction: column;
		gap: 1rem;
		max-width: 400px;
	}
</style>
