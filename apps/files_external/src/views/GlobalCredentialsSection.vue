<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { addPasswordConfirmationInterceptors, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import logger from '../utils/logger.ts'

const globalCredentials = loadState<{
	uid: string
	user: string
	password: string
}>('files_external', 'global-credentials')

const loading = ref(false)
const username = ref(globalCredentials.user)
const password = ref(globalCredentials.password)

addPasswordConfirmationInterceptors(axios)

/**
 * Submit the global credentials form
 */
async function onSubmit() {
	try {
		loading.value = true
		const { data } = await axios.post<boolean>(generateUrl('apps/files_external/globalcredentials'), {
			// This is the UID of the user to save the credentials (admins can set that also for other users)
			uid: globalCredentials.uid,
			user: username.value,
			password: password.value,
		}, { confirmPassword: PwdConfirmationMode.Strict })
		if (data) {
			showSuccess(t('files_external', 'Global credentials saved'))
			return
		}
	} catch (e) {
		logger.error(e as Error)
		// Error is handled below
	} finally {
		loading.value = false
	}
	// result was false so show an error
	showError(t('files_external', 'Could not save global credentials'))
	username.value = globalCredentials.user
	password.value = globalCredentials.password
}
</script>

<template>
	<NcSettingsSection
		:name="t('files_external', 'Global credentials')"
		:description="t('files_external', 'Global credentials can be used to authenticate with multiple external storages that have the same credentials.')">
		<form
			id="global_credentials"
			:class="$style.globalCredentialsSectionForm"
			autocomplete="false"
			@submit.prevent="onSubmit">
			<NcTextField
				v-model="username"
				name="username"
				autocomplete="false"
				:label="t('files_external', 'Login')" />
			<NcPasswordField
				v-model="password"
				name="password"
				autocomplete="false"
				:label="t('files_external', 'Password')" />
			<NcButton
				:class="$style.globalCredentialsSectionForm__submit"
				:disabled="loading"
				variant="primary"
				type="submit">
				{{ loading ? t('files_external', 'Saving …') : t('files_external', 'Save') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<style module>
.globalCredentialsSectionForm {
	max-width: 400px;
	display: flex;
	flex-direction: column;
	align-items: end;
	gap: 15px;
}

.globalCredentialsSectionForm__submit {
	min-width: max(40%, 44px);
}
</style>
