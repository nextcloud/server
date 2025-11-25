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

		<p class="settings-hint">
			{{ t('settings', 'Enable or disable profile by default for new accounts.') }}
		</p>

		<NcCheckboxRadioSwitch
			v-model="initialProfileEnabledByDefault"
			type="switch"
			@update:modelValue="onProfileDefaultChange">
			{{ t('settings', 'Enable') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import logger from '../../logger.ts'
import { saveProfileDefault } from '../../service/ProfileService.js'
import { validateBoolean } from '../../utils/validate.js'

const profileEnabledByDefault = loadState('settings', 'profileEnabledByDefault', true)

export default {
	name: 'ProfileSettings',

	components: {
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			initialProfileEnabledByDefault: profileEnabledByDefault,
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
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update profile default setting'),
					error: e,
				})
			}
		},

		handleResponse({ isEnabled, status, errorMessage, error }) {
			if (status === 'ok') {
				this.initialProfileEnabledByDefault = isEnabled
			} else {
				showError(errorMessage)
				logger.error(errorMessage, error)
			}
		},
	},
}
</script>
