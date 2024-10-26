<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="checkbox-container">
		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="isProfileEnabled"
			:loading="loading"
			@update:checked="saveEnableProfile">
			{{ t('settings', 'Enable profile') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { emit } from '@nextcloud/event-bus'

import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { ACCOUNT_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import { handleError } from '../../../utils/handlers.ts'

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
