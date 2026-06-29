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
				:model-value="value"
				@input="onInput" />
			<VisibilityScopeControl
				class="property__scope"
				:readable="birthdate.readable"
				:name="birthdate.name"
				:scope="birthdate.scope"
				@update:scope="onScopeChange" />
		</div>
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

export default {
	name: 'BirthdaySection',

	components: {
		NcDateTimePickerNative,
		VisibilityScopeControl,
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

			/** @param {Date} value The date to set */
			set(value) {
				const day = value.getDate().toString().padStart(2, '0')
				const month = (value.getMonth() + 1).toString().padStart(2, '0')
				const year = value.getFullYear()
				this.birthdate.value = `${year}-${month}-${day}`
			},
		},
	},

	methods: {
		onScopeChange(scope) {
			this.birthdate.scope = scope
		},

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
.property-section {
	padding: 6px 0;
}

.property {
	position: relative;

	&__field {
		width: 100%;
	}

	&__scope {
		position: absolute;
		inset-block-start: 0;
		inset-inline-start: calc(100% + 8px);
		display: flex;
		align-items: center;
		justify-content: center;
		width: var(--default-clickable-area);
		height: var(--default-clickable-area);
	}
}
</style>
