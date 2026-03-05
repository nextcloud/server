<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div
		id="profile-settings"
		class="section">
		<h2 class="inlineblock">
			{{ t('settings', 'Profile') }}
		</h2>

		<NcNoteCard type="info">
			{{ t('settings', 'Enable or disable profile by default for new accounts.') }}
		</NcNoteCard>

		<NcCheckboxRadioSwitch
			v-model="initialProfileEnabledByDefault"
			type="switch"
			@update:modelValue="onProfileDefaultChange">
			{{ t('settings', 'Enable') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch
			v-model="initialProfilePickerEnabled"
			type="switch"
			@update:modelValue="onProfilePickerChange">
			{{ t('settings', 'Enable the profile picker') }}
		</NcCheckboxRadioSwitch>

		<NcNoteCard type="info">
			{{ t('settings', 'Enable or disable the profile picker in the Smart Picker and the profile link previews.') }}
		</NcNoteCard>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import logger from '../../logger.ts'
import { saveProfileDefault, saveProfilePicker } from '../../service/ProfileService.js'
import { validateBoolean } from '../../utils/validate.js'

const profileEnabledByDefault = loadState('settings', 'profileEnabledByDefault', true)
const profilePickerEnabled = loadState('settings', 'profilePickerEnabled', true)

export default {
	name: 'ProfileSettings',

	components: {
		NcCheckboxRadioSwitch,
		NcNoteCard,
	},

	data() {
		return {
			initialProfileEnabledByDefault: profileEnabledByDefault,
			initialProfilePickerEnabled: profilePickerEnabled,
		}
	},

	methods: {
		async onProfileDefaultChange(isEnabled) {
			if (validateBoolean(isEnabled)) {
				await this.updateProfileDefault(isEnabled)
			}
		},

		async updateProfileDefault(isEnabled) {
			try {
				const responseData = await saveProfileDefault(isEnabled)
				this.handleResponse({
					isEnabled,
					key: 'initialProfileEnabledByDefault',
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update profile default setting'),
					error: e,
				})
			}
		},

		async onProfilePickerChange(isEnabled) {
			if (validateBoolean(isEnabled)) {
				await this.updateProfilePicker(isEnabled)
			}
		},

		async updateProfilePicker(isEnabled) {
			try {
				const responseData = await saveProfilePicker(isEnabled)
				this.handleResponse({
					isEnabled,
					key: 'initialProfilePickerEnabled',
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update profile picker setting'),
					error: e,
				})
			}
		},

		handleResponse({ isEnabled, key, status, errorMessage, error }) {
			if (status === 'ok') {
				this[key] = isEnabled
			} else {
				showError(errorMessage)
				logger.error(errorMessage, error)
			}
		},
	},
}
</script>

<style scoped lang="scss">
#profile-settings {
	max-width: 600px;
}
</style>
