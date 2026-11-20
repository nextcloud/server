<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { reactive } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import logger from '../../logger.ts'
import { saveProfileDefault, saveProfilePicker, saveProfilePrivate } from '../../service/ProfileService.js'
import { validateBoolean } from '../../utils/validate.js'

const settings = reactive({
	profileEnabledByDefault: loadState('settings', 'profileEnabledByDefault', true),
	profilePickerEnabled: loadState('settings', 'profilePickerEnabled', true),
	profilePrivate: loadState('settings', 'profilePrivate', true),
})

/**
 * @param key The switch that was toggled
 * @param save The service call that stores the setting
 * @param isEnabled The new value
 * @param errorMessage Shown when saving fails
 */
async function updateSetting(key: keyof typeof settings, save: (isEnabled: boolean) => Promise<unknown>, isEnabled: boolean, errorMessage: string) {
	if (!validateBoolean(isEnabled)) {
		return
	}
	try {
		await save(isEnabled)
	} catch (error) {
		logger.error(errorMessage, { error })
		settings[key] = !isEnabled
		showError(errorMessage)
	}
}
</script>

<template>
	<div
		id="profile-settings"
		class="section"
		:class="[$style.profileSettings]">
		<h2 class="inlineblock">
			{{ t('settings', 'Profile') }}
		</h2>

		<NcNoteCard type="info">
			{{ t('settings', 'Enable or disable profile by default for new accounts.') }}
		</NcNoteCard>

		<NcCheckboxRadioSwitch
			v-model="settings.profileEnabledByDefault"
			type="switch"
			@update:modelValue="updateSetting('profileEnabledByDefault', saveProfileDefault, $event, t('settings', 'Unable to update profile default setting'))">
			{{ t('settings', 'Enable by default') }}
		</NcCheckboxRadioSwitch>

		<NcNoteCard type="info">
			{{ t('settings', 'Enable or disable the profile picker in the Smart Picker and the profile link previews.') }}
		</NcNoteCard>

		<NcCheckboxRadioSwitch
			v-model="settings.profilePickerEnabled"
			type="switch"
			@update:modelValue="updateSetting('profilePickerEnabled', saveProfilePicker, $event, t('settings', 'Unable to update profile picker setting'))">
			{{ t('settings', 'Enable the profile picker') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch
			v-model="settings.profilePrivate"
			type="switch"
			@update:modelValue="updateSetting('profilePrivate', saveProfilePrivate, $event, t('settings', 'Unable to update profile private setting'))">
			{{ t('settings', 'Prevent unauthenticated users from seeing user profiles') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<style module>
.profileSettings {
	max-width: 600px;
}
</style>
