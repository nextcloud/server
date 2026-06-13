<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showInfo } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import SettingsPersonalChangePrivateKey from '../components/SettingsPersonalChangePrivateKey.vue'
import SettingsPersonalEnableRecovery from '../components/SettingsPersonalEnableRecovery.vue'
import { logger } from '../utils/logger.ts'
import { InitStatus } from '../utils/types.ts'

const personalSettings = loadState<{
	recoveryEnabled: boolean
	recoveryEnabledForUser: boolean
	privateKeySet: boolean
	initialized: typeof InitStatus[keyof typeof InitStatus]
}>('encryption', 'personalSettings')

const initialized = ref(personalSettings.initialized)
const recoveryEnabledForUser = ref(personalSettings.recoveryEnabledForUser)

/**
 * Reload encryption status
 */
async function reloadStatus() {
	try {
		const { data } = await axios.get(generateUrl('/apps/encryption/ajax/getStatus'))
		initialized.value = data.initStatus
		if (data.data.message) {
			showInfo(data.data.message)
		}
	} catch (error) {
		logger.error('Failed to fetch current encryption status', { error })
	}
}
</script>

<template>
	<NcSettingsSection :name="t('encryption', 'Basic encryption module')">
		<NcNoteCard v-if="initialized === InitStatus.NotInitialized" type="warning">
			{{ t('encryption', 'Encryption app is enabled but your keys are not initialized, please log-out and log-in again') }}
		</NcNoteCard>

		<SettingsPersonalChangePrivateKey
			v-else-if="initialized === InitStatus.InitExecuted"
			:recoveryEnabledForUser
			@updated="reloadStatus" />
		<SettingsPersonalEnableRecovery
			v-else-if="personalSettings.recoveryEnabled && personalSettings.privateKeySet"
			v-model="recoveryEnabledForUser" />
	</NcSettingsSection>
</template>
