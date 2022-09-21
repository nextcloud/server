<!--
	- @copyright 2022 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
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

import { saveProfileDefault } from '../../service/ProfileService'
import { validateBoolean } from '../../utils/validate'
import logger from '../../logger'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch'

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

<style lang="scss" scoped>
</style>
