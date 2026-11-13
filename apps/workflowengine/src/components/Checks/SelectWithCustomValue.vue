<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSelect
			:aria-label-combobox="comboboxLabel"
			:class="$style.select"
			:clearable="false"
			:modelValue="currentValue"
			:options="options"
			:placeholder="placeholder"
			label="label"
			@update:modelValue="onSelect">
			<template #option="option">
				<span :class="$style.icon">
					<NcIconSvgWrapper
						v-if="option.iconPath"
						:path="option.iconPath"
						:size="20"
						inline />
					<img v-else-if="option.iconUrl" :src="option.iconUrl" alt="">
				</span>
				<span :class="$style.title">
					<NcEllipsisedOption :name="String(option.label)" />
				</span>
			</template>
			<template #selected-option="selectedOption">
				<span :class="$style.icon">
					<NcIconSvgWrapper
						v-if="selectedOption.iconPath"
						:path="selectedOption.iconPath"
						:size="20"
						inline />
					<img v-else-if="selectedOption.iconUrl" :src="selectedOption.iconUrl" alt="">
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

import { mdiCog } from '@mdi/js'
import { computed } from 'vue'
import NcEllipsisedOption from '@nextcloud/vue/components/NcEllipsisedOption'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useCheckValue } from '../../composables/useCheckValue.ts'

const props = withDefaults(defineProps<{
	modelValue?: string
	/** The values offered besides free text */
	predefinedValues: PredefinedValue[]
	/** Label of the entry that switches to free text */
	customLabel: string
	/** Accessible name of the combobox */
	comboboxLabel: string
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
	iconPath: mdiCog,
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

.icon {
	display: inline-block;
	min-width: 30px;
	text-align: center;
	vertical-align: middle;
}

.title {
	display: inline-flex;
	width: calc(100% - 36px);
	vertical-align: middle;
}
</style>
