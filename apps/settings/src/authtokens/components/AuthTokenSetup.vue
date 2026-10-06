<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITokenResponse } from '../store/authtoken.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import AuthTokenSetupDialog from './AuthTokenSetupDialog.vue'
import logger from '../../logger.ts'
import { useAuthTokenStore } from '../store/authtoken.ts'

const authTokenStore = useAuthTokenStore()

const deviceName = ref('')
const loading = ref(false)
const newToken = ref<ITokenResponse | null>(null)

/**
 * Clear the form and close the new app password dialog
 */
function reset() {
	loading.value = false
	deviceName.value = ''
	newToken.value = null
}

/**
 * Create an app password with the entered name
 */
async function submit() {
	try {
		loading.value = true
		newToken.value = await authTokenStore.addToken(deviceName.value)
	} catch (error) {
		logger.error(error as Error)
		showError(t('settings', 'Error while creating device token'))
		reset()
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<form
		id="generate-app-token-section"
		@submit.prevent="submit">
		<!-- Port to TextField component when available -->
		<NcTextField
			v-model="deviceName"
			type="text"
			:maxlength="120"
			:disabled="loading"
			class="app-name-text-field"
			:label="t('settings', 'App name')"
			:placeholder="t('settings', 'App name')" />
		<NcButton
			variant="primary"
			:disabled="loading || deviceName.length === 0"
			type="submit">
			{{ t('settings', 'Create new app password') }}
		</NcButton>

		<AuthTokenSetupDialog :token="newToken" @close="newToken = null" />
	</form>
</template>

<style lang="scss" scoped>
	#generate-app-token-section {
		display: flex;
		flex-direction: column;
		gap: 1rem;
		max-width: 400px;
		padding-top: 16px;
	}
</style>
