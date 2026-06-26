<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="checkbox-container">
		<NcCheckboxRadioSwitch
			v-model="isProfileEnabled"
			type="switch"
			:loading="loading"
			@update:modelValue="saveEnableProfile">
			{{ t('settings', 'Enable profile') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import { emit } from '@nextcloud/event-bus'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { ACCOUNT_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
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
