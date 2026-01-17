<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { NcNoteCard, NcSettingsSection } from '@nextcloud/vue'
import { ref } from 'vue'
import SettingsAdminHomeStorage from '../components/SettingsAdminHomeStorage.vue'
import SettingsAdminRecoveryKey from '../components/SettingsAdminRecoveryKey.vue'
import SettingsAdminRecoveryKeyChange from '../components/SettingsAdminRecoveryKeyChange.vue'
import { InitStatus } from '../utils/types.ts'

const adminSettings = loadState<{
	recoveryEnabled: boolean
	masterKeyEnabled: boolean
	encryptHomeStorage: boolean
	initStatus: typeof InitStatus[keyof typeof InitStatus]
}>('encryption', 'adminSettings')

const encryptHomeStorage = ref(adminSettings.encryptHomeStorage!)
const recoveryEnabled = ref(adminSettings.recoveryEnabled!)
</script>

<template>
	<NcSettingsSection :name="t('encryption', 'Default encryption module')">
		<NcNoteCard v-if="adminSettings.initStatus === InitStatus.NotInitialized && !adminSettings.masterKeyEnabled" type="warning">
			{{ t('encryption', 'Encryption app is enabled but your keys are not initialized, please log-out and log-in again') }}
		</NcNoteCard>

		<template v-else>
			<SettingsAdminHomeStorage v-model="encryptHomeStorage" />
			<br>
			<SettingsAdminRecoveryKey v-if="adminSettings.masterKeyEnabled" v-model="recoveryEnabled" />
			<SettingsAdminRecoveryKeyChange v-if="adminSettings.masterKeyEnabled && recoveryEnabled" />
		</template>
	</NcSettingsSection>
</template>
