<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import SettingsSanitizeFilenames from '../components/Settings/SettingsSanitizeFilenames.vue'
import logger from '../logger.ts'

const {
	docUrl,
	isRunningSanitization,
	windowsSupport,
} = loadState<{ docUrl: string, isRunningSanitization: boolean, windowsSupport: boolean }>('files', 'filesCompatibilitySettings')

const description = t('files', 'Allow to restrict filenames to ensure files can be synced with all clients. By default all filenames valid on POSIX (e.g. Linux or macOS) are allowed.')
	+ '\n' + t('files', 'After enabling the Windows compatible filenames, existing files cannot be modified anymore but can be renamed to valid new names by their owner.')

const loading = ref(false)
const hasWindowsSupport = ref(windowsSupport)

/**
 * Toggle the Windows filename support on the backend.
 *
 * @param enabled - The new state to be set
 */
async function toggleWindowsFilenameSupport(enabled: boolean) {
	if (loading.value) {
		return
	}

	try {
		loading.value = true
		await axios.post(generateOcsUrl('apps/files/api/v1/filenames/windows-compatibility'), { enabled })
		hasWindowsSupport.value = enabled
	} catch (error) {
		showError(t('files', 'Failed to toggle Windows filename support'))
		logger.error('Failed to toggle Windows filename support', { error })
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<NcSettingsSection
		:doc-url="docUrl"
		:name="t('files', 'Files compatibility')"
		:description="description">
		<NcCheckboxRadioSwitch
			:model-value="hasWindowsSupport"
			:disabled="isRunningSanitization"
			:loading="loading"
			type="switch"
			@update:model-value="toggleWindowsFilenameSupport">
			{{ t('files', 'Enforce Windows compatibility') }}
		</NcCheckboxRadioSwitch>
		<p class="hint">
			{{ t('files', 'This will block filenames not valid on Windows systems, like using reserved names or special characters. But this will not enforce compatibility of case sensitivity.') }}
		</p>

		<SettingsSanitizeFilenames v-if="hasWindowsSupport" />
	</NcSettingsSection>
</template>

<style scoped>
.hint {
	color: var(--color-text-maxcontrast);
	margin-inline-start: var(--border-radius-element);
	margin-block-end: 1em;
}
</style>
