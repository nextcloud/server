<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<section class="property-section">
		<div class="property">
			<NcDateTimePickerNative
				:id="inputId"
				class="property__field"
				type="date"
				:label="birthdate.readable"
				:model-value="timezoneAdjustedValue"
				@input="onInput" />
			<VisibilityScopeControl
				class="property__scope"
				:readable="birthdate.readable"
				:name="birthdate.name"
				:scope="birthdate.scope"
				@update:scope="onScopeChange" />
		</div>

		<p class="property__helper-text-message">
			{{ t('settings', 'Enter your date of birth') }}
		</p>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import debounce from 'debounce'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import VisibilityScopeControl from './shared/VisibilityScopeControl.vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../utils/handlers.js'

const { birthdate } = loadState('settings', 'personalInfoParameters', {})

/**
 * Convert a birthdate string value into a Date.
 *
 * @param {string } birthdateValue - e.g. "1987-12-01" or "1987-12-01T00:00:00.000Z"
 * @return {Date}
 */
function birthdateValueToDate(birthdateValue) {
	return new Date(birthdateValue)
}

export default {
	name: 'BirthdaySection',

	components: {
		NcDateTimePickerNative,
		VisibilityScopeControl,
	},

	data() {
		let initialValue = null
		if (birthdate.value) {
			initialValue = birthdateValueToDate(birthdate.value)
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

		value() {
			return birthdateValueToDate(this.birthdate.value)
		},

		/**
		 * Adjusted value for usage with `NcDateTimePickerNative` (internally `<input="date">`)
		 * The saved value is is UTC and we want to show it the same regardless of the browsers/OSs timezone.
		 * When the adjusted value is displayed and the users timezone is applied, this adjusted value then looks like the UTC value.
		 */
		timezoneAdjustedValue() {
			// example: this.birthdate.value === '1987-12-01T00:00:00.000Z' or '1987-12-01'

			// example: Mon Nov 30 1987 16:00:00 GMT-0800 (Pacific Standard Time)
			// `NcDateTimePickerNative` would show this as 11/30/1987
			const date = this.value
			const timezoneOffsetMilliseconds = date.getTimezoneOffset() * 60 * 1000
			const adjustedDate = new Date(date.getTime() + timezoneOffsetMilliseconds)

			// example: Tue Dec 01 1987 00:00:00 GMT-0800 (Pacific Standard Time)
			// `NcDateTimePickerNative` will show this as 12/01/1987
			return adjustedDate
		},
	},

	methods: {
		onScopeChange(scope) {
			this.birthdate.scope = scope
		},

		onInput(e) {
			const day = e.getDate().toString().padStart(2, '0')
			const month = (e.getMonth() + 1).toString().padStart(2, '0')
			const year = e.getFullYear()
			this.birthdate.value = `${year}-${month}-${day}`
			this.debouncePropertyChange(this.value)
		},

		debouncePropertyChange: debounce(async function(value) {
			await this.updateProperty(value)
		}, 500),

		async updateProperty(value) {
			try {
				const responseData = await savePrimaryAccountProperty(
					this.birthdate.name,
					value.toISOString(),
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
.property-section {
	padding: 6px 0;
}

.property {
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: 8px;

	&__field {
		flex: 1 1 auto;
		min-width: 0;
	}

	&__scope {
		flex: 0 0 var(--default-clickable-area);
		display: flex;
		justify-content: center;
	}

	&__helper-text-message {
		color: var(--color-text-maxcontrast);
		padding: 4px 0;
		display: flex;
		align-items: center;
	}
}
</style>
