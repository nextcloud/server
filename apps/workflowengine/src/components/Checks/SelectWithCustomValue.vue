<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSelect
			:aria-label-combobox="ariaLabel"
			:class="$style.select"
			:clearable="false"
			:modelValue="currentValue"
			:options="options"
			:placeholder="placeholder"
			label="label"
			@update:modelValue="onSelect">
			<template #option="option">
				<span v-if="option.icon" :class="[$style.icon, option.icon]" />
				<span v-else-if="option.iconUrl" :class="$style.iconImage">
					<img :src="option.iconUrl" alt="">
				</span>
				<span :class="$style.title">
					<NcEllipsisedOption :name="String(option.label)" />
				</span>
			</template>
			<template #selected-option="selectedOption">
				<span v-if="selectedOption.icon" :class="[$style.icon, selectedOption.icon]" />
				<span v-else-if="selectedOption.iconUrl" :class="$style.iconImage">
					<img :src="selectedOption.iconUrl" alt="">
				</span>
				<span :class="$style.title">
					<NcEllipsisedOption :name="String(selectedOption.label)" />
				</span>
			</template>
		</NcSelect>
		<input
			v-if="!isPredefined"
			:class="$style.customValue"
			:placeholder="customPlaceholder"
			:value="newValue"
			type="text"
			@input="onCustomInput">
	</div>
</template>

<script setup lang="ts">
import type { PredefinedValue } from '../../types.ts'

import { computed } from 'vue'
import NcEllipsisedOption from '@nextcloud/vue/components/NcEllipsisedOption'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useCheckValue } from '../../composables/useCheckValue.ts'

const props = withDefaults(defineProps<{
	modelValue?: string
	/** The values offered besides free text */
	predefinedValues: PredefinedValue[]
	/** Label of the entry that switches to free text */
	customLabel: string
	/** Accessible name of the combobox */
	ariaLabel: string
	/** Placeholder of the combobox */
	placeholder: string
	/** Placeholder of the free text input */
	customPlaceholder?: string
}>(), {
	modelValue: '',
	customPlaceholder: undefined,
})

const emit = defineEmits<{
	'update:modelValue': [value: string]
}>()

const { newValue, emitValue } = useCheckValue(
	() => props.modelValue,
	(value) => emit('update:modelValue', value),
)

const customValue = computed<PredefinedValue>(() => ({
	icon: 'icon-settings-dark',
	label: props.customLabel,
	id: '',
}))

const options = computed(() => [...props.predefinedValues, customValue.value])

const matchingPredefined = computed(() => props.predefinedValues.find((value) => value.id === newValue.value))

const isPredefined = computed(() => matchingPredefined.value !== undefined)

const currentValue = computed<PredefinedValue>(() => matchingPredefined.value ?? { ...customValue.value, id: newValue.value })

/**
 * Take a predefined value, or switch to free text when the custom entry was
 * picked, which carries an empty id.
 *
 * @param value - The option that was picked
 */
function onSelect(value: PredefinedValue | null): void {
	if (value !== null) {
		emitValue(value.id)
	}
}

/**
 * @param event - The input event of the free text field
 */
function onCustomInput(event: Event): void {
	emitValue((event.target as HTMLInputElement).value)
}
</script>

<style module lang="scss">
.select,
.customValue {
	width: 100%;
}

.customValue {
	min-height: 48px;
}

.icon,
.iconImage {
	display: inline-block;
	min-width: 30px;
	background-position: center;
	vertical-align: middle;
}

.iconImage {
	text-align: center;
}

.title {
	display: inline-flex;
	width: calc(100% - 36px);
	vertical-align: middle;
}
</style>
