<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<section>
		<HeaderBar :scope="birthdate.scope"
			:input-id="inputId"
			:readable="birthdate.readable" />

		<template>
			<NcDateTimePickerNative :id="inputId"
				type="date"
				label=""
				:value="value"
				@input="onInput" />
		</template>

		<p class="property__helper-text-message">
			{{ t('settings', 'Enter your date of birth') }}
		</p>
	</section>
</template>

<script>
import HeaderBar from './shared/HeaderBar.vue'
import AccountPropertySection from './shared/AccountPropertySection.vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'
import { NcDateTimePickerNative } from '@nextcloud/vue'
import debounce from 'debounce'
import { savePrimaryAccountProperty } from '../../service/PersonalInfo/PersonalInfoService'
import { handleError } from '../../utils/handlers'
import AlertCircle from 'vue-material-design-icons/AlertCircleOutline.vue'
import { loadState } from '@nextcloud/initial-state'

const { birthdate } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'BirthdaySection',

	components: {
		AlertCircle,
		AccountPropertySection,
		NcDateTimePickerNative,
		HeaderBar,
	},

	data() {
		let initialValue = null
		if (birthdate.value) {
			initialValue = new Date(birthdate.value)
		}

		return {
			birthdate: {
				...birthdate,
				readable: NAME_READABLE_ENUM[birthdate.name],
			},
			initialValue,
		}
	},

	computed: {
		inputId() {
			return `account-property-${birthdate.name}`
		},
		value: {
			get() {
				return new Date(this.birthdate.value)
			},
			/** @param {Date} value */
			set(value) {
				const day = value.getDate().toString().padStart(2, '0')
				const month = (value.getMonth() + 1).toString().padStart(2, '0')
				const year = value.getFullYear()
				this.birthdate.value = `${year}-${month}-${day}`
			}
		},
	},

	methods: {
		onInput(e) {
			this.value = e
			this.debouncePropertyChange(this.value)
		},

		debouncePropertyChange: debounce(async function(value) {
			await this.updateProperty(value)
		}, 500),

		async updateProperty(value) {
			try {
				const responseData = await savePrimaryAccountProperty(
					this.birthdate.name,
					value,
				)
				this.handleResponse({
					value,
					status: responseData.ocs?.meta?.status,
				})
			} catch (error) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update date of birth'),
					error,
				})
			}
		},

		handleResponse({ value, status, errorMessage, error }) {
			if (status === 'ok') {
				this.initialValue = value
			} else {
				this.$emit('update:value', this.initialValue)
				handleError(error, errorMessage)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;

	&::v-deep button:disabled {
		cursor: default;
	}

	.property__helper-text-message {
		color: var(--color-text-maxcontrast);
		padding: 4px 0;
		display: flex;
		align-items: center;
	}
}
</style>
