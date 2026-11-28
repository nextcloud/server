<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { DeclarativeForm, DeclarativeFormField, DeclarativeFormOption, DeclarativeTextFieldType } from './types.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { useDebounceFn } from '@vueuse/core'
import { reactive } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { handleError } from '../../utils/handlers.ts'
import { TEXT_TYPES } from './types.ts'

const props = defineProps<{
	/** The form with the current values of its fields */
	form: DeclarativeForm
}>()

const formApp = props.form.app

const values = reactive<Record<string, unknown>>({})
const savedValues: Record<string, unknown> = {}
/** Per field, so typing in one field doesn't cancel another field's pending save */
const saveField: Record<string, () => void> = {}
/** Number of the latest save per field, only that one may roll the field back */
const latestSave: Record<string, number> = {}

for (const field of props.form.fields) {
	// values are replaced on change, never changed in place, so both can share it
	values[field.id] = savedValues[field.id] = initialValue(field)
	saveField[field.id] = isTextField(field) ? useDebounceFn(() => save(field), 1000) : () => save(field)
}

/**
 * @param field The form field
 */
function initialValue(field: DeclarativeFormField): unknown {
	if (field.type === 'checkbox') {
		return Boolean(Number(field.value))
	}
	if (field.type === 'multi-checkbox') {
		const stored = field.value === '' ? {} : JSON.parse(field.value as string) as Record<string, boolean>
		return Object.fromEntries((field.options as DeclarativeFormOption[])
			.map((option) => [option.value, stored[option.value] ?? false]))
	}
	if (field.type === 'multi-select') {
		return field.value === '' ? [] : JSON.parse(field.value as string)
	}
	return field.value
}

/**
 * @param field The form field
 */
function isTextField(field: DeclarativeFormField): field is DeclarativeFormField & { type: DeclarativeTextFieldType } {
	return (TEXT_TYPES as readonly string[]).includes(field.type)
}

/**
 * @param text The text, if the app set one
 */
function tApp(text?: string): string | undefined {
	return text ? t(formApp, text) : undefined
}

/**
 * @param field The form field
 * @param value The new value
 */
function update(field: DeclarativeFormField, value: unknown) {
	values[field.id] = value
	saveField[field.id]!()
}

/**
 * @param field The form field
 * @param option Value of the option
 * @param checked Whether the option is checked
 */
function updateOption(field: DeclarativeFormField, option: string, checked: boolean) {
	update(field, { ...(values[field.id] as Record<string, boolean>), [option]: checked })
}

/**
 * @param field The form field
 * @param value The value of the field
 */
function serverValue(field: DeclarativeFormField, value: unknown): unknown {
	if (field.type === 'checkbox') {
		return Number(value)
	}
	if (field.type === 'multi-checkbox' || field.type === 'multi-select') {
		return JSON.stringify(value)
	}
	return value
}

/**
 * On failure choices go back to the saved value, text stays so it can be retried.
 *
 * @param field The form field
 */
async function save(field: DeclarativeFormField) {
	const value = values[field.id]
	const run = latestSave[field.id] = (latestSave[field.id] ?? 0) + 1
	if (field.sensitive) {
		try {
			await confirmPassword()
		} catch {
			showError(t('settings', 'Password confirmation is required'))
			return
		}
	}

	try {
		await axios.post(generateOcsUrl(field.sensitive ? 'settings/api/declarative/value-sensitive' : 'settings/api/declarative/value'), {
			app: formApp,
			formId: props.form.id,
			fieldId: field.id,
			value: serverValue(field, value),
		})
		savedValues[field.id] = value
	} catch (error) {
		handleError(error, t('settings', 'Failed to save setting'))
		if (!isTextField(field) && run === latestSave[field.id]) {
			values[field.id] = savedValues[field.id]
		}
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t(formApp, form.title)"
		:description="tApp(form.description)"
		:docUrl="form.doc_url || undefined">
		<div
			v-for="field in form.fields"
			:key="field.id"
			:class="$style.field">
			<NcInputField
				v-if="isTextField(field)"
				:class="$style.input"
				:modelValue="values[field.id] as string"
				:type="field.type"
				:label="t(formApp, field.title)"
				:placeholder="tApp(field.placeholder)"
				:helperText="tApp(field.description)"
				@update:modelValue="update(field, $event)" />

			<NcSelect
				v-else-if="field.type === 'select' || field.type === 'multi-select'"
				:class="$style.input"
				:modelValue="values[field.id]"
				:options="field.options"
				:multiple="field.type === 'multi-select'"
				:inputLabel="t(formApp, field.title)"
				:placeholder="tApp(field.placeholder)"
				:helperText="tApp(field.description)"
				@update:modelValue="update(field, $event)" />

			<component
				:is="field.label ? NcFormGroup : 'div'"
				v-else-if="field.type === 'checkbox'"
				:label="field.label ? t(formApp, field.title) : undefined">
				<NcCheckboxRadioSwitch
					:modelValue="values[field.id] as boolean"
					type="switch"
					:description="tApp(field.description)"
					@update:modelValue="update(field, $event)">
					{{ t(formApp, field.label ?? field.title) }}
				</NcCheckboxRadioSwitch>
			</component>

			<NcFormGroup
				v-else-if="field.type === 'multi-checkbox' || field.type === 'radio'"
				:label="t(formApp, field.title)"
				:description="tApp(field.description)">
				<div :class="$style.options">
					<template v-if="field.type === 'radio'">
						<NcCheckboxRadioSwitch
							v-for="option in field.options as DeclarativeFormOption[]"
							:key="option.value"
							:modelValue="values[field.id] as string"
							:value="option.value"
							:name="`${formApp}_${form.id}_${field.id}`"
							type="radio"
							@update:modelValue="update(field, $event)">
							{{ t(formApp, option.name) }}
						</NcCheckboxRadioSwitch>
					</template>
					<template v-else>
						<NcCheckboxRadioSwitch
							v-for="option in field.options as DeclarativeFormOption[]"
							:key="option.value"
							:modelValue="(values[field.id] as Record<string, boolean>)[option.value]"
							@update:modelValue="updateOption(field, option.value, $event)">
							{{ t(formApp, option.name) }}
						</NcCheckboxRadioSwitch>
					</template>
				</div>
			</NcFormGroup>
		</div>
	</NcSettingsSection>
</template>

<style module>
.field {
	padding-block: calc(var(--default-grid-baseline) * 2);
}

.input {
	width: 100%;
	max-width: calc(var(--default-grid-baseline) * 100);
}

.options {
	max-height: calc(var(--default-clickable-area) * 6);
	overflow-y: auto;
}
</style>
