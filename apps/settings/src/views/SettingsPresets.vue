<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { PresetAppConfigs, PresetIds } from '../components/SettingsPresets/models.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import PresetsSelectionForm from '../components/SettingsPresets/PresetsSelectionForm.vue'
import PresetVisualisation from '../components/SettingsPresets/PresetVisualisation.vue'
import logger from '../logger.ts'

const presets = loadState('settings', 'settings-presets', {}) as PresetAppConfigs
const currentPreset = ref(loadState('settings', 'settings-selected-preset', 'NONE') as PresetIds)
const selectedPreset = ref(currentPreset.value)
const savingPreset = ref(false)

/**
 *
 */
async function saveSelectedPreset() {
	try {
		savingPreset.value = true
		await axios.post(generateUrl('/settings/preset/current'), {
			presetName: selectedPreset.value,
		})
		currentPreset.value = selectedPreset.value
	} catch (error) {
		showError(t('settings', 'Failed to save selected preset.'))
		logger.error('Error saving selected preset:', { error })
		selectedPreset.value = currentPreset.value
	} finally {
		savingPreset.value = false
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('settings', 'Quick presets')"
		:description="t('settings', 'Select a configuration preset for easy setup.')">
		<PresetsSelectionForm v-model="selectedPreset" :presets="presets" />

		<PresetVisualisation :presets="presets" :selected-preset="selectedPreset" />

		<NcButton
			class="save-button"
			:disabled="selectedPreset === currentPreset || savingPreset"
			variant="primary"
			@click="saveSelectedPreset()">
			{{ t('settings', 'Apply') }}

			<template v-if="savingPreset" #icon>
				<NcLoadingIcon />
			</template>
		</NcButton>
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
.save-button {
	margin-top: 16px;
}
</style>
