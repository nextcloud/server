<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { refreshStyles } from '../utils/refreshStyles.ts'

const enableBlurFilter = ref(loadState('theming', 'enableBlurFilter', '') === 'no')
const shortcutsDisabled = ref(loadState('theming', 'shortcutsDisabled', false))

/**
 * Persist the blur filter preference and reload theming styles.
 *
 * @param disabled - Whether the blur filter should be forced off
 */
async function changeDisableBlurFilter(disabled: boolean) {
	const url = generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
		appId: 'theming',
		configKey: 'force_enable_blur_filter',
	})

	await axios.post(url, {
		configValue: disabled ? 'no' : 'yes',
	})
	refreshStyles()
}

/**
 * Persist the keyboard shortcut preference.
 *
 * @param disabled - Whether keyboard shortcuts should be disabled
 */
async function updateHotkeyState(disabled: boolean) {
	const url = generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
		appId: 'theming',
		configKey: 'shortcuts_disabled',
	})

	if (disabled) {
		await axios.post(url, {
			configValue: 'yes',
		})
	} else {
		await axios.delete(url)
	}
}
</script>

<template>
	<NcSettingsSection :name="t('theming', 'Compatibility')">
		<NcFormBox class="compatibility-options">
			<NcFormBoxSwitch
				v-model="shortcutsDisabled"
				:label="t('theming', 'Disable keyboard shortcuts')"
				@update:modelValue="updateHotkeyState" />
			<NcFormBoxSwitch
				v-model="enableBlurFilter"
				:label="t('theming', 'Disable background blur')"
				@update:modelValue="changeDisableBlurFilter" />
		</NcFormBox>
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
.compatibility-options {
	gap: calc(2 * var(--default-grid-baseline));
}
</style>
