<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
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
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<div class="checkbox-container">
		<input
			id="enable-profile"
			class="checkbox"
			type="checkbox"
			:checked="profileEnabled"
			@change="onEnableProfileChange">
		<label for="enable-profile">
			{{ t('settings', 'Enable Profile') }}
		</label>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'

import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService'
import { validateBoolean } from '../../../utils/validate'
import { ACCOUNT_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants'

export default {
	name: 'ProfileCheckbox',

	props: {
		profileEnabled: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			initialProfileEnabled: this.profileEnabled,
		}
	},

	methods: {
		async onEnableProfileChange(e) {
			const isEnabled = e.target.checked
			this.$emit('update:profile-enabled', isEnabled)

			if (validateBoolean(isEnabled)) {
				await this.updateEnableProfile(isEnabled)
			}
		},

		async updateEnableProfile(isEnabled) {
			try {
				const responseData = await savePrimaryAccountProperty(ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED, isEnabled)
				this.handleResponse({
					isEnabled,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update profile enabled state'),
					error: e,
				})
			}
		},

		handleResponse({ isEnabled, status, errorMessage, error }) {
			if (status === 'ok') {
				// Ensure that local state reflects server state
				this.initialProfileEnabled = isEnabled
				emit('settings:profile-enabled:updated', isEnabled)
			} else {
				showError(errorMessage)
				this.logger.error(errorMessage, error)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
</style>
