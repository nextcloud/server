<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'

const shortcutsDisabled = ref(loadState('theming', 'shortcutsDisabled', false))
watch(shortcutsDisabled, updateHotkeyState)

/**
 * Update the hotkey state on the server
 */
async function updateHotkeyState() {
	const url = generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
		appId: 'theming',
		configKey: 'shortcuts_disabled',
	})

	if (shortcutsDisabled.value) {
		await axios.post(url, {
			configValue: 'yes',
		})
	} else {
		await axios.delete(url)
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('theming', 'Keyboard shortcuts')"
		:description="t('theming', 'In some cases keyboard shortcuts can interfere with accessibility tools. In order to allow focusing on your tool correctly you can disable all keyboard shortcuts here. This will also disable all available shortcuts in apps.')">
		<NcCheckboxRadioSwitch
			v-model="shortcutsDisabled"
			class="theming__preview-toggle"
			type="switch">
			{{ t('theming', 'Disable all keyboard shortcuts') }}
		</NcCheckboxRadioSwitch>
	</NcSettingsSection>
</template>
