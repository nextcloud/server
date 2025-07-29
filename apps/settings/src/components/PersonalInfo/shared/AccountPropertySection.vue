<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<HeaderBar :scope="scope"
			:readable="readable"
			:input-id="inputId"
			:is-editable="isEditable"
			@update:scope="(scope) => $emit('update:scope', scope)" />

		<div v-if="isEditable" class="property">
			<NcTextArea v-if="multiLine"
				:id="inputId"
				autocapitalize="none"
				autocomplete="off"
				:error="hasError || !!helperText"
				:helper-text="helperText"
				label-outside
				:placeholder="placeholder"
				rows="8"
				spellcheck="false"
				:success="isSuccess"
				:value.sync="inputValue" />
			<NcInputField v-else
				:id="inputId"
				ref="input"
				autocapitalize="none"
				:autocomplete="autocomplete"
				:error="hasError || !!helperText"
				:helper-text="helperText"
				label-outside
				:placeholder="placeholder"
				spellcheck="false"
				:success="isSuccess"
				:type="type"
				:value.sync="inputValue" />
		</div>
		<span v-else>
			{{ value || t('settings', 'No {property} set', { property: readable.toLocaleLowerCase() }) }}
		</span>
	</section>
</template>

<script>
import debounce from 'debounce'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'

import HeaderBar from './HeaderBar.vue'

import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

export default {
	name: 'AccountPropertySection',

	components: {
		HeaderBar,
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
				setTimeout(() => { this.isSuccess = false }, 2000)
			} else {
				handleError(error, errorMessage)
				this.hasError = true
			}
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;

	.property {
		display: flex;
		flex-direction: row;
		align-items: start;
		gap: 4px;

		.property__actions-container {
			margin-top: 6px;
			justify-self: flex-end;
			align-self: flex-end;

			display: flex;
			gap: 0 2px;
			margin-inline-end: 5px;
			margin-bottom: 5px;
		}
	}

	.property__helper-text-message {
		padding: 4px 0;
		display: flex;
		align-items: center;

		&__icon {
			margin-inline-end: 8px;
			align-self: start;
			margin-top: 4px;
		}

		&--error {
			color: var(--color-error);
		}
	}

	.fade-enter,
	.fade-leave-to {
		opacity: 0;
	}

	.fade-enter-active {
		transition: opacity 200ms ease-out;
	}

	.fade-leave-active {
		transition: opacity 300ms ease-out;
	}
}
</style>
