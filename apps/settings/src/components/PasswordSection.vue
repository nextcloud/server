<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection :name="t('settings', 'Password')">
		<form id="passwordform" method="POST" @submit.prevent="changePassword">
			<NcPasswordField id="old-pass"
				:label="t('settings', 'Current password')"
				name="oldpassword"
				:value.sync="oldPass"
				autocomplete="current-password"
				autocapitalize="none"
				spellcheck="false" />

			<NcPasswordField id="new-pass"
				:label="t('settings', 'New password')"
				:value.sync="newPass"
				:maxlength="469"
				autocomplete="new-password"
				autocapitalize="none"
				spellcheck="false"
				:check-password-strength="true" />

			<NcButton type="primary"
				native-type="submit"
				:disabled="newPass.length === 0 || oldPass.length === 0">
				{{ t('settings', 'Change password') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'

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
				.then(res => res.data)
				.then(data => {
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
