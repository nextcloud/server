<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div ref="checkElement" :class="$style.check" @click="showDelete">
		<NcSelect
			ref="checkSelector"
			:aria-label-combobox="t('workflowengine', 'Filter')"
			:class="$style.filter"
			:clearable="false"
			:modelValue="currentOption"
			:options="options"
			:placeholder="t('workflowengine', 'Select a filter')"
			label="name"
			@update:modelValue="onFilterChange" />
		<NcSelect
			:aria-label-combobox="t('workflowengine', 'Comparator')"
			:class="$style.comparator"
			:clearable="false"
			:disabled="!currentOption"
			:modelValue="currentOperator"
			:options="operators"
			:placeholder="t('workflowengine', 'Select a comparator')"
			label="name"
			@update:modelValue="onComparatorChange" />
		<!-- A custom element takes hyphenated attributes. `disabled` has to fall
			away entirely when it does not apply: on a dashed tag Vue writes the
			literal string "false", which the element would read as truthy. -->
		<!-- eslint-disable vue/attribute-hyphenation -->
		<component
			:is="currentElement"
			v-if="currentElement"
			ref="valueElement"
			:class="$style.option"
			:disabled="!currentOption || undefined"
			:model-value="check.value"
			:operator="check.operator" />
		<!-- eslint-enable vue/attribute-hyphenation -->
		<input
			v-else
			:class="[$style.option, { [$style.invalid]: !valid }]"
			:disabled="!currentOption"
			:placeholder="valuePlaceholder"
			:value="check.value"
			type="text"
			@input="onValueInput">
		<NcActions v-if="deleteVisible || !currentOption">
			<NcActionButton :title="t('workflowengine', 'Remove filter')" @click="emit('remove')">
				<template #icon>
					<NcIconSvgWrapper :path="mdiClose" :size="20" />
				</template>
			</NcActionButton>
		</NcActions>
	</div>
</template>

<script setup lang="ts">
/* eslint vue/multi-word-component-names: "warn" */

import type { CheckPlugin, Check as CheckType, Comparison, Rule } from '../types.ts'

import { mdiClose } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { onClickOutside } from '@vueuse/core'
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useCustomElementEvents } from '../composables/useCustomElementEvents.ts'
import { logger } from '../logger.ts'
import { useWorkflowStore } from '../store.ts'

const props = defineProps<{
	check: CheckType
	rule: Rule
}>()

const emit = defineEmits<{
	update: [check: CheckType]
	validate: [valid: boolean]
	remove: []
}>()

const store = useWorkflowStore()

const checkElement = useTemplateRef<HTMLDivElement>('checkElement')
const valueElement = useTemplateRef<Element>('valueElement')
const checkSelector = useTemplateRef<{ focus?: () => void }>('checkSelector')

const deleteVisible = ref(false)
const valid = ref(false)
/** Undefined until a custom element reports on its own value. */
const elementValid = ref<boolean | undefined>(undefined)

const checks = computed(() => store.checksForEntity(props.rule.entity))
const options = computed(() => Object.values(checks.value))

/**
 * Both selections are derived from the check itself, so a row always shows what
 * it actually holds — including after a neighbouring row was removed and this
 * component was reused for a different check.
 */
const currentOption = computed<CheckPlugin | null>(() => (props.check.class === null ? null : checks.value[props.check.class] ?? null))

const operators = computed<Comparison[]>(() => {
	if (!currentOption.value) {
		return []
	}
	const { operators } = currentOption.value
	return typeof operators === 'function' ? operators(props.check) : operators
})

const currentOperator = computed<Comparison | null>(() => operators.value.find((operator) => operator.operator === props.check.operator) ?? null)

const currentElement = computed(() => {
	if (props.check.class === null) {
		return undefined
	}
	return checks.value[props.check.class]?.element
})

const valuePlaceholder = computed(() => currentOption.value?.placeholder?.(props.check) ?? '')

function showDelete(): void {
	deleteVisible.value = true
}

onClickOutside(checkElement, () => {
	deleteVisible.value = false
})

/**
 * A check is valid unless the plugin says otherwise. Only when the plugin
 * brings no validator does the verdict of its custom element count, so a
 * plugin validator always wins.
 */
function validate(): void {
	if (currentOption.value?.validate) {
		valid.value = Boolean(currentOption.value.validate(props.check))
	} else {
		valid.value = elementValid.value ?? true
	}
	store.updateCheck(props.check, { invalid: !valid.value })
	emit('validate', valid.value)
}

/**
 * @param patch - The fields of the check to change
 */
function update(patch: Partial<CheckType>): void {
	store.updateCheck(props.check, patch)
	validate()
	emit('update', props.check)
}

/**
 * @param option - The filter that was picked
 */
function onFilterChange(option: CheckPlugin | null): void {
	if (option === null) {
		return
	}
	if (!option.element && !option.validate && (option as { component?: unknown }).component) {
		logger.error(`Check plugin "${option.class}" only provides the removed "component" option. `
			+ 'Provide a custom element through "element" instead.')
	}

	const available = typeof option.operators === 'function' ? option.operators(props.check) : option.operators
	// the comparison of the previous filter may not exist for the new one
	const operator = available.some((item) => item.operator === props.check.operator)
		? props.check.operator
		: available[0]?.operator ?? null

	if (option.class !== props.check.class) {
		elementValid.value = undefined
	}
	update({ class: option.class, operator })
}

/**
 * @param comparison - The comparison that was picked
 */
function onComparatorChange(comparison: Comparison | null): void {
	if (comparison !== null) {
		update({ operator: comparison.operator })
	}
}

/**
 * @param event - The change a custom element reported
 */
function onElementValue(event: CustomEvent<unknown[]>): void {
	update({ value: event.detail[0] as string })
}

/**
 * @param reported - What the custom element reported about its own value
 */
function onElementValidity(reported: boolean): void {
	elementValid.value = reported
	validate()
}

/**
 * @param event - The input event of the plain value field
 */
function onValueInput(event: Event): void {
	update({ value: (event.target as HTMLInputElement).value })
}

useCustomElementEvents(valueElement, {
	'update:model-value': onElementValue,
	valid: () => onElementValidity(true),
	invalid: () => onElementValidity(false),
})

watch(() => props.check.operator, validate)

onMounted(() => {
	if (props.check.class === null) {
		checkSelector.value?.focus?.()
	}
	validate()
})
</script>

<style module lang="scss">
.check {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-start; // to not stretch components vertically
	width: 100%;
	padding-inline-end: 20px;

	& > * {
		margin-inline-end: 5px;
		margin-bottom: 5px;
	}
}

.filter {
	width: 180px;
}

.comparator {
	min-width: 200px;
	width: 200px;
}

.option {
	min-width: 260px;
	width: 260px;
	min-height: 48px;

	input[type='text'] {
		min-height: 48px;
	}
}

input.option {
	margin-top: 0;
}

.invalid {
	border-color: var(--color-border-error) !important;
}
</style>
