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
		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="isProfileEnabled"
			:loading="loading"
			@update:checked="saveEnableProfile">
			{{ t('settings', 'Enable Profile') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { emit } from '@nextcloud/event-bus'

import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { ACCOUNT_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import { handleError } from '../../../utils/handlers.js'

export default {
	name: 'ProfileCheckbox',

	components: {
		NcCheckboxRadioSwitch,
	},

	props: {
		profileEnabled: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			isProfileEnabled: this.profileEnabled,
			loading: false,
		}
	},

	methods: {
		async saveEnableProfile() {
			this.loading = true
			try {
				const responseData = await savePrimaryAccountProperty(ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED, this.isProfileEnabled)
				this.handleResponse({
					isProfileEnabled: this.isProfileEnabled,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update profile enabled state'),
					error: e,
				})
			}
		},

		handleResponse({ isProfileEnabled, status, errorMessage, error }) {
			if (status === 'ok') {
				emit('settings:profile-enabled:updated', isProfileEnabled)
			} else {
				handleError(error, errorMessage)
			}
			this.loading = false
		},
	},
}
</script>
