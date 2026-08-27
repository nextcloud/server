<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcFormBox } from '@nextcloud/vue'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { AUTH_TOKENS_REVOKED_EVENT } from '../constants/AuthTokenConstants.ts'

const passwordform = ref<HTMLFormElement>()

const oldPass = ref('')
const newPass = ref('')
const revokeOtherSessions = ref(false)

/**
 * Change the user's password
 */
async function changePassword() {
	const { data } = await axios.post(generateUrl('/settings/personal/changepassword'), {
		oldpassword: oldPass.value,
		newpassword: newPass.value,
		revokeOtherSessions: revokeOtherSessions.value,
	})
	if (data.status === 'error') {
		showError(data.data.message)
	} else {
		showSuccess(data.data.message)
		emit(AUTH_TOKENS_REVOKED_EVENT, data.data.revokedTokenIds)
		oldPass.value = ''
		newPass.value = ''
		revokeOtherSessions.value = false
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

			<NcCheckboxRadioSwitch
				v-model="revokeOtherSessions"
				aria-describedby="password-revoke-other-sessions-hint">
				{{ t('settings', 'Sign out all other devices and apps') }}
			</NcCheckboxRadioSwitch>
			<p
				id="password-revoke-other-sessions-hint"
				:class="$style.passwordSection__hint">
				{{ t('settings', 'Sync clients and connected apps lose access and have to sign in again. This device stays signed in.') }}
			</p>

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

.passwordSection__hint {
	color: var(--color-text-maxcontrast);
}
</style>
