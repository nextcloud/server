<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcInputField from '@nextcloud/vue/components/NcInputField'

defineProps<{
	/** The stored value, `null` when unset */
	value: number | null
	label: string
	helperText: string
	min: number
	max?: number
	/** Shown when the value is unset */
	placeholder?: string
	disabled?: boolean
}>()

const emit = defineEmits<{
	/** A valid value was committed, `null` when the field was emptied */
	change: [value: number | null]
}>()

/**
 * Commit the value once the field is left, if the browser accepts it
 *
 * @param event The change event of the input
 */
function onChange(event: Event): void {
	const input = event.target as HTMLInputElement
	if (!input.reportValidity()) {
		return
	}
	emit('change', input.value === '' ? null : input.valueAsNumber)
}
</script>

<template>
	<NcInputField
		:modelValue="value ?? ''"
		type="number"
		:label="label"
		:helperText="helperText"
		:min="min"
		:max="max"
		:placeholder="placeholder"
		:disabled="disabled"
		@change="onChange" />
</template>
