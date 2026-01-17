<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcFormBox } from '@nextcloud/vue'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

const passwordform = ref<HTMLFormElement>()

const oldPass = ref('')
const newPass = ref('')

/**
 * Change the user's password
 */
async function changePassword() {
	const { data } = await axios.post(generateUrl('/settings/personal/changepassword'), {
		oldpassword: oldPass.value,
		newpassword: newPass.value,
	})
	if (data.status === 'error') {
		showError(data.data.message)
	} else {
		showSuccess(data.data.message)
		oldPass.value = ''
		newPass.value = ''
		passwordform.value?.reset()
	}
}
</script>

<template>
	<NcSettingsSection :name="t('settings', 'Password')">
		<form
			ref="passwordform"
			:class="$style.passwordSection__form"
			@submit.prevent="changePassword">
			<NcFormBox>
				<NcPasswordField
					v-model="oldPass"
					:label="t('settings', 'Current password')"
					name="oldpassword"
					autocomplete="current-password"
					autocapitalize="none"
					required
					spellcheck="false" />

				<NcPasswordField
					v-model="newPass"
					check-password-strength
					:label="t('settings', 'New password')"
					:maxlength="469"
					name="newpassword"
					autocomplete="new-password"
					autocapitalize="none"
					required
					spellcheck="false" />
			</NcFormBox>

			<NcButton
				type="submit"
				variant="primary"
				wide>
				{{ t('settings', 'Change password') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<style module>
.passwordSection__form {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
	max-width: 300px !important;
}
</style>
