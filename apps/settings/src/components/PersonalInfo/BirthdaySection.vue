<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<section>
		<HeaderBar :scope="birthdate.scope"
			:input-id="inputId"
			:readable="birthdate.readable" />

		<!-- Use non-native picker to ensure locale-consistent formatting across browsers -->
		<NcDateTimePicker :id="inputId"
			:type="'date'"
			:format="formatDateLocalized"
			:placeholder="placeholderLocalized"
			:value="value"
			@update:value="onInput" />

		<p class="property__helper-text-message">
			{{ t('settings', 'Enter your date of birth') }}
		</p>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { getCanonicalLocale } from '@nextcloud/l10n'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../service/PersonalInfo/PersonalInfoService'
import { handleError } from '../../utils/handlers'

import debounce from 'debounce'

import NcDateTimePicker from '@nextcloud/vue/components/NcDateTimePicker'
import HeaderBar from './shared/HeaderBar.vue'

const { birthdate } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'BirthdaySection',

	components: {
		NcDateTimePicker,
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
		// Canonical BCP-47 locale (e.g., "de-CH") used by the native date input
		canonicalLocale() {
			return getCanonicalLocale()
		},
		// Intl formatter for localized date display
		intlDtf() {
			return new Intl.DateTimeFormat(this.canonicalLocale, {
				year: 'numeric', month: '2-digit', day: '2-digit',
			})
		},
		// A function the picker uses to format the visible value
		formatDateLocalized() {
			return (date) => (date instanceof Date ? this.intlDtf.format(date) : '')
		},
		// Localized placeholder like dd.mm.yyyy / dd/mm/yyyy etc.
		placeholderLocalized() {
			const probe = new Date(2001, 1, 3) // 03 Feb 2001
			const parts = this.intlDtf.formatToParts(probe)
			return parts.map(p => {
				if (p.type === 'day') return 'dd'
				if (p.type === 'month') return 'mm'
				if (p.type === 'year') return 'yyyy'
				return p.value
			}).join('')
		},
		inputId() {
			return `account-property-${birthdate.name}`
		},
		value: {
			get() {
				if (!this.birthdate?.value) {
					return null
				}
				const d = new Date(this.birthdate.value)
				return isNaN(d.getTime()) ? null : d
			},
			/** @param {Date} value The date to set */
			set(value) {
				if (!(value instanceof Date) || isNaN(value.getTime())) {
					// keep empty if not a valid date
					this.birthdate.value = ''
					return
				}
				const day = value.getDate().toString().padStart(2, '0')
				const month = (value.getMonth() + 1).toString().padStart(2, '0')
				const year = value.getFullYear()
				this.birthdate.value = `${year}-${month}-${day}`
			},
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

	:deep(button:disabled) {
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
