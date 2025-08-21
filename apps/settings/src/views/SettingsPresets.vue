<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcButton from '@nextcloud/vue/components/NcButton'
import { loadState } from '@nextcloud/initial-state'

import PresetsSelectionForm from '../components/SettingsPresets/PresetsSelectionForm.vue'
import PresetVisualisation from '../components/SettingsPresets/PresetVisualisation.vue'
import type { PresetAppConfigs } from '../components/SettingsPresets/models'
import logger from '../logger'
import { showError } from '@nextcloud/dialogs'

const presets = loadState('settings', 'settings-presets', {}) as PresetAppConfigs
const selectedPreset = ref(loadState('settings', 'settings-selected-preset', 'NONE'))

async function saveSelectedPreset() {
	// TODO: Implement the logic to save the selected preset
	try {
		await axios.post(generateUrl('/settings/presets'), {
			preset: selectedPreset.value,
		})
	} catch (error) {
		showError(t('settings', 'Failed to save selected preset.'))
		logger.error('Error saving selected preset:', { error })
	}
}
</script>

<template>
	<NcSettingsSection :name="t('settings', 'Settings presets')"
		:description="t('settings', 'You can select a settings preset to quickly configure your Nextcloud instance.')">
		<PresetsSelectionForm v-model="selectedPreset" :presets="presets" />

		<PresetVisualisation :presets="presets" :selected-preset="selectedPreset" />

		<div class="save-button-container">
			<NcButton variante="primary"
				@click="saveSelectedPreset()">
				{{ t('settings', 'Apply selected preset') }}
			</NcButton>
		</div>
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
.save-button-container {
	display: flex;
	justify-content: flex-end;
	margin-top: 16px;
}
</style>
