<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection class="declarative-settings-section"
		:name="t(formApp, form.title)"
		:description="t(formApp, form.description)"
		:doc-url="form.doc_url || ''">
		<div v-for="formField in formFields"
			:key="formField.id"
			class="declarative-form-field"
			:aria-label="t('settings', '{app}\'s declarative setting field: {name}', { app: formApp, name: t(formApp, formField.title) })"
			:class="{
				'declarative-form-field-text': isTextFormField(formField),
				'declarative-form-field-select': formField.type === 'select',
				'declarative-form-field-multi-select': formField.type === 'multi-select',
				'declarative-form-field-checkbox': formField.type === 'checkbox',
				'declarative-form-field-multi_checkbox': formField.type === 'multi-checkbox',
				'declarative-form-field-radio': formField.type === 'radio'
			}">
			<template v-if="isTextFormField(formField)">
				<div class="input-wrapper">
					<NcInputField :type="formField.type"
						:label="t(formApp, formField.title)"
						:value.sync="formFieldsData[formField.id].value"
						:placeholder="t(formApp, formField.placeholder)"
						@update:value="onChangeDebounced(formField)"
						@submit="updateDeclarativeSettingsValue(formField)" />
				</div>
				<span v-if="formField.description" class="hint">{{ t(formApp, formField.description) }}</span>
			</template>

			<template v-if="formField.type === 'select'">
				<label :for="formField.id + '_field'">{{ t(formApp, formField.title) }}</label>
				<div class="input-wrapper">
					<NcSelect :id="formField.id + '_field'"
						:options="formField.options"
						:placeholder="t(formApp, formField.placeholder)"
						:label-outside="true"
						:value="formFieldsData[formField.id].value"
						@input="(value) => updateFormFieldDataValue(value, formField, true)" />
				</div>
				<span v-if="formField.description" class="hint">{{ t(formApp, formField.description) }}</span>
			</template>

			<template v-if="formField.type === 'multi-select'">
				<label :for="formField.id + '_field'">{{ t(formApp, formField.title) }}</label>
				<div class="input-wrapper">
					<NcSelect :id="formField.id + '_field'"
						:options="formField.options"
						:placeholder="t(formApp, formField.placeholder)"
						:multiple="true"
						:label-outside="true"
						:value="formFieldsData[formField.id].value"
						@input="(value) => {
							formFieldsData[formField.id].value = value
							updateDeclarativeSettingsValue(formField, JSON.stringify(formFieldsData[formField.id].value))
						}
						" />
				</div>
				<span v-if="formField.description" class="hint">{{ t(formApp, formField.description) }}</span>
			</template>

			<template v-if="formField.type === 'checkbox'">
				<label v-if="formField.label" :for="formField.id + '_field'">{{ t(formApp, formField.title) }}</label>
				<NcCheckboxRadioSwitch :id="formField.id + '_field'"
					:checked="Boolean(formFieldsData[formField.id].value)"
					type="switch"
					@update:checked="(value) => {
						formField.value = value
						updateFormFieldDataValue(+value, formField, true)
					}
					">
					{{ t(formApp, formField.label ?? formField.title) }}
				</NcCheckboxRadioSwitch>
				<span v-if="formField.description" class="hint">{{ t(formApp, formField.description) }}</span>
			</template>

			<template v-if="formField.type === 'multi-checkbox'">
				<label :for="formField.id + '_field'">{{ t(formApp, formField.title) }}</label>
				<NcCheckboxRadioSwitch v-for="option in formField.options"
					:id="formField.id + '_field_' + option.value"
					:key="option.value"
					:checked="formFieldsData[formField.id].value[option.value]"
					@update:checked="(value) => {
						formFieldsData[formField.id].value[option.value] = value
						// Update without re-generating initial formFieldsData.value object as the link to components are lost
						updateDeclarativeSettingsValue(formField, JSON.stringify(formFieldsData[formField.id].value))
					}
					">
					{{ t(formApp, option.name) }}
				</NcCheckboxRadioSwitch>
				<span v-if="formField.description" class="hint">{{ t(formApp, formField.description) }}</span>
			</template>

			<template v-if="formField.type === 'radio'">
				<label :for="formField.id + '_field'">{{ t(formApp, formField.title) }}</label>
				<NcCheckboxRadioSwitch v-for="option in formField.options"
					:key="option.value"
					:value="option.value"
					type="radio"
					:checked="formFieldsData[formField.id].value"
					@update:checked="(value) => updateFormFieldDataValue(value, formField, true)">
					{{ t(formApp, option.name) }}
				</NcCheckboxRadioSwitch>
				<span v-if="formField.description" class="hint">{{ t(formApp, formField.description) }}</span>
			</template>
		</div>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import debounce from 'debounce'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import { confirmPassword } from '@nextcloud/password-confirmation'

export default {
	name: 'DeclarativeSection',
	components: {
		NcSettingsSection,
		NcInputField,
		NcSelect,
		NcCheckboxRadioSwitch,
	},
	props: {
		form: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			formFieldsData: {},
		}
	},
	computed: {
		formApp() {
			return this.form.app || ''
		},
		formFields() {
			return this.form.fields || []
		},
	},
	beforeMount() {
		this.initFormFieldsData()
	},
	methods: {
		initFormFieldsData() {
			this.form.fields.forEach((formField) => {
				if (formField.type === 'checkbox') {
					// convert bool to number using unary plus (+) operator
					this.$set(formField, 'value', +formField.value)
				}
				if (formField.type === 'multi-checkbox') {
					if (formField.value === '') {
						// Init formFieldsData from options
						this.$set(formField, 'value', {})
						formField.options.forEach(option => {
							this.$set(formField.value, option.value, false)
						})
					} else {
						this.$set(formField, 'value', JSON.parse(formField.value))
						// Merge possible new options
						formField.options.forEach(option => {
							if (!Object.prototype.hasOwnProperty.call(formField.value, option.value)) {
								this.$set(formField.value, option.value, false)
							}
						})
						// Remove options that are not in the form anymore
						Object.keys(formField.value).forEach(key => {
							if (!formField.options.find(option => option.value === key)) {
								delete formField.value[key]
							}
						})
					}
				}
				if (formField.type === 'multi-select') {
					if (formField.value === '') {
						// Init empty array for multi-select
						this.$set(formField, 'value', [])
					} else {
						// JSON decode an array of multiple values set
						this.$set(formField, 'value', JSON.parse(formField.value))
					}
				}
				this.$set(this.formFieldsData, formField.id, {
					value: formField.value,
				})
			})
		},

		updateFormFieldDataValue(value, formField, update = false) {
			this.formFieldsData[formField.id].value = value
			if (update) {
				this.updateDeclarativeSettingsValue(formField)
			}
		},

		async updateDeclarativeSettingsValue(formField, value = null) {
			try {
				let url = generateOcsUrl('settings/api/declarative/value')
				if (formField?.sensitive === true) {
					url = generateOcsUrl('settings/api/declarative/value-sensitive')
					try {
						await confirmPassword()
					} catch (err) {
						showError(t('settings', 'Password confirmation is required'))
						return
					}
				}
				return axios.post(url, {
					app: this.formApp,
					formId: this.form.id.replace(this.formApp + '_', ''), // Remove app prefix to send clean form id
					fieldId: formField.id,
					value: value === null ? this.formFieldsData[formField.id].value : value,
				})
			} catch (err) {
				console.debug(err)
				showError(t('settings', 'Failed to save setting'))
			}
		},

		onChangeDebounced: debounce(function(formField) {
			this.updateDeclarativeSettingsValue(formField)
		}, 1000),

		isTextFormField(formField) {
			return ['text', 'password', 'email', 'tel', 'url', 'number'].includes(formField.type)
		},
	},
}
</script>

<style lang="scss" scoped>
.declarative-form-field {
	padding: 10px 0;

	.input-wrapper {
		width: 100%;
		max-width: 400px;
	}

	&:last-child {
		border-bottom: none;
	}

	.hint {
		display: inline-block;
		color: var(--color-text-maxcontrast);
		margin-inline-start: 8px;
		padding-block-start: 5px;
	}

	&-radio, &-multi_checkbox {
		max-height: 250px;
		overflow-y: auto;
	}

	&-multi-select, &-select {
		display: flex;
		flex-direction: column;

		label {
			margin-bottom: 5px;
		}
	}
}
</style>
