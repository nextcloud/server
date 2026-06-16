<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="property-section">
		<div class="property">
			<NcTextArea
				v-if="multiLine"
				:id="inputId"
				v-model="inputValue"
				class="property__field"
				autocapitalize="none"
				autocomplete="off"
				:disabled="!isEditable"
				:error="hasError || !!helperText"
				:helper-text="helperText"
				:label="readable"
				:placeholder="placeholder"
				rows="8"
				spellcheck="false"
				:success="isSuccess" />
			<NcInputField
				v-else
				:id="inputId"
				ref="input"
				v-model="inputValue"
				class="property__field"
				autocapitalize="none"
				:autocomplete="autocomplete"
				:disabled="!isEditable"
				:error="hasError || !!helperText"
				:helper-text="helperText"
				:label="readable"
				:placeholder="placeholder"
				spellcheck="false"
				:success="isSuccess"
				:type="type" />

			<FederationControl
				class="property__scope"
				:readable="readable"
				:scope="scope"
				:disabled="!isEditable"
				@update:scope="(scope) => $emit('update:scope', scope)" />
		</div>
	</section>
</template>

<script>
import debounce from 'debounce'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import FederationControl from './FederationControl.vue'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

export default {
	name: 'AccountPropertySection',

	components: {
		FederationControl,
		NcInputField,
		NcTextArea,
	},

	props: {
		name: {
			type: String,
			required: true,
		},

		value: {
			type: String,
			required: true,
		},

		scope: {
			type: String,
			required: true,
		},

		readable: {
			type: String,
			required: true,
		},

		placeholder: {
			type: String,
			required: true,
		},

		type: {
			type: String,
			default: 'text',
		},

		isEditable: {
			type: Boolean,
			default: true,
		},

		multiLine: {
			type: Boolean,
			default: false,
		},

		onValidate: {
			type: Function,
			default: null,
		},

		onSave: {
			type: Function,
			default: null,
		},

		autocomplete: {
			type: String,
			default: null,
		},
	},

	emits: ['update:scope', 'update:value'],

	data() {
		return {
			initialValue: this.value,
			helperText: '',
			isSuccess: false,
			hasError: false,
		}
	},

	computed: {
		inputId() {
			return `account-property-${this.name}`
		},

		inputValue: {
			get() {
				return this.value
			},

			set(value) {
				this.$emit('update:value', value)
				this.debouncePropertyChange(value.trim())
			},
		},

		debouncePropertyChange() {
			return debounce(async function(value) {
				this.helperText = this.$refs.input?.$refs.input?.validationMessage || ''
				if (this.helperText !== '') {
					return
				}
				this.hasError = this.onValidate && !this.onValidate(value)
				if (this.hasError) {
					this.helperText = t('settings', 'Invalid value')
					return
				}
				await this.updateProperty(value)
			}, 1000)
		},
	},

	methods: {
		async updateProperty(value) {
			try {
				this.hasError = false
				const responseData = await savePrimaryAccountProperty(
					this.name,
					value,
				)
				this.handleResponse({
					value,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update {property}', { property: this.readable.toLocaleLowerCase() }),
					error: e,
				})
			}
		},

		handleResponse({ value, status, errorMessage, error }) {
			if (status === 'ok') {
				this.initialValue = value
				if (this.onSave) {
					this.onSave(value)
				}
				this.isSuccess = true
				setTimeout(() => {
					this.isSuccess = false
				}, 2000)
			} else {
				handleError(error, errorMessage)
				this.hasError = true
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
		flex: 0 0 44px;
		display: flex;
		justify-content: center;
	}
}
</style>
