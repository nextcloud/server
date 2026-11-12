<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { useDebounceFn } from '@vueuse/core'
import { ref, useTemplateRef } from 'vue'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import VisibilityScopeControl from './VisibilityScopeControl.vue'
import { savePrimaryAccountProperty } from '../../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../../utils/handlers.ts'

/** Value of the property */
const value = defineModel<string>('value', { required: true })

/** Federation scope of the property */
const scope = defineModel<string>('scope', { required: true })

const props = withDefaults(defineProps<{
	/** Key of the account property */
	name: string
	/** Human readable name of the property, used as label */
	readable: string
	/** Placeholder of the field */
	placeholder: string
	/** Input type of the single line field */
	type?: 'text' | 'url' | 'tel' | 'email'
	/** Whether the property can not be changed */
	disabled?: boolean
	/** Show a text area instead of a single line field */
	multiLine?: boolean
	/** Whether a value may be saved */
	onValidate?: (value: string) => boolean
	/** Called with the value after it was saved */
	onSave?: (value: string) => void
	/** Autocomplete hint of the single line field */
	autocomplete?: string
}>(), {
	type: 'text',
	disabled: false,
	multiLine: false,
	onValidate: undefined,
	onSave: undefined,
	autocomplete: undefined,
})

const inputField = useTemplateRef('inputField')

const helperText = ref('')
const isSuccess = ref(false)
const hasError = ref(false)

const debouncePropertyChange = useDebounceFn(async (newValue: string) => {
	helperText.value = inputField.value?.$el.querySelector('input')?.validationMessage ?? ''
	if (helperText.value !== '') {
		return
	}
	hasError.value = props.onValidate !== undefined && !props.onValidate(newValue)
	if (hasError.value) {
		helperText.value = t('settings', 'Invalid value')
		return
	}
	await updateProperty(newValue)
}, 1000)

/**
 * @param newValue The entered value
 */
function onInput(newValue: string | number) {
	// the model only updates once the parent re-renders, so don't read it back here
	const entered = String(newValue)
	value.value = entered
	debouncePropertyChange(entered.trim())
}

/**
 * @param newValue The value to store
 */
async function updateProperty(newValue: string) {
	hasError.value = false
	try {
		await savePrimaryAccountProperty(props.name, newValue)
		props.onSave?.(newValue)
		isSuccess.value = true
		setTimeout(() => {
			isSuccess.value = false
		}, 2000)
	} catch (error) {
		handleError(error, t('settings', 'Unable to update {property}', { property: props.readable.toLocaleLowerCase() }))
		hasError.value = true
	}
}
</script>

<template>
	<section :class="$style.propertySection">
		<div :class="$style.property">
			<NcTextArea
				v-if="multiLine"
				:id="`account-property-${name}`"
				:class="$style.field"
				:modelValue="value"
				autocapitalize="none"
				autocomplete="off"
				:disabled="disabled"
				:error="hasError || !!helperText"
				:helperText="helperText"
				:label="readable"
				:placeholder="placeholder"
				rows="8"
				spellcheck="false"
				:success="isSuccess"
				@update:modelValue="onInput" />
			<NcInputField
				v-else
				:id="`account-property-${name}`"
				ref="inputField"
				:class="$style.field"
				:modelValue="value"
				autocapitalize="none"
				:autocomplete="autocomplete"
				:disabled="disabled"
				:error="hasError || !!helperText"
				:helperText="helperText"
				:label="readable"
				:placeholder="placeholder"
				spellcheck="false"
				:success="isSuccess"
				:type="type"
				@update:modelValue="onInput" />

			<div :class="$style.scope">
				<VisibilityScopeControl
					v-model:scope="scope"
					:readable="readable"
					:name="name"
					:disabled="disabled" />
			</div>
		</div>
	</section>
</template>

<style module>
.propertySection {
	padding: 6px 0;
}

.property {
	position: relative;
}

.field {
	width: 100%;
}

.scope {
	position: absolute;
	inset-block-start: 0;
	inset-inline-start: calc(100% + 8px);
	display: flex;
	align-items: center;
	justify-content: center;
	width: var(--default-clickable-area);
	height: var(--default-clickable-area);
}
</style>
