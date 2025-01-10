<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="profile-settings"
		class="section">
		<h2 class="inlineblock">
			{{ t('settings', 'Profile') }}
		</h2>

		<p class="settings-hint">
			{{ t('settings', 'Enable or disable profile by default for new accounts.') }}
		</p>

		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="initialProfileEnabledByDefault"
			@update:checked="onProfileDefaultChange">
			{{ t('settings', 'Enable') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'

import { saveProfileDefault } from '../../service/ProfileService.js'
import { validateBoolean } from '../../utils/validate.js'
import logger from '../../logger.ts'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

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
