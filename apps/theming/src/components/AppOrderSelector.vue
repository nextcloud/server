<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IApp } from './AppOrderSelectorElement.vue'

import { t } from '@nextcloud/l10n'
import { useSortable } from '@vueuse/integrations/useSortable'
import { computed, onUpdated, ref } from 'vue'
import AppOrderSelectorElement from './AppOrderSelectorElement.vue'

defineOptions({
	inheritAttrs: false,
})

/**
 * List of apps to reorder
 */
const modelValue = defineModel<IApp[]>({ required: true })

defineProps<{
	/**
	 * Details like status information that need to be forwarded to the interactive elements
	 */
	ariaDetails: string
}>()

/**
 * The Element that contains the app list
 */
const listElement = ref<HTMLElement | null>(null)

/**
 * Helper to force rerender the list in case of a invalid drag event
 */
const renderCount = ref(0)

/**
 * The app list with setter that will ement the `update:value` event
 */
const appList = computed({
	get: () => modelValue.value,
	// Ensure the sortable.js does not mess with the default attribute
	set: (list) => {
		const newValue = [...list]
			.sort((a, b) => ((b.default ? 1 : 0) - (a.default ? 1 : 0)) || list.indexOf(a) - list.indexOf(b))

		if (newValue.some(({ id }, index) => id !== modelValue.value.at(index)?.id)) {
			modelValue.value = newValue
		} else {
			// forceUpdate as the DOM has changed because of a drag event, but the reactive state has not -> wrong state
			renderCount.value += 1
		}
	},
})

/**
 * Handle drag & drop sorting
 */
useSortable(listElement, appList, { filter: '.order-selector-element--disabled' })

/**
 * Array of all AppOrderSelectorElement components used to for keeping the focus after button click
 */
const selectorElements = ref<InstanceType<typeof AppOrderSelectorElement>[]>([])

/**
 * We use the updated hook here to verify all selector elements keep the focus on the last pressed button
 * This is needed to be done in this component to make sure Sortable.JS has finished sorting the elements before focussing an element
 */
onUpdated(() => {
	selectorElements.value.forEach((element) => element.keepFocus())
})

/**
 * Handle element is moved up
 *
 * @param index The index of the element that is moved
 */
function moveUp(index: number) {
	const before = index > 1 ? modelValue.value.slice(0, index - 1) : []
	// skip if not possible, because of default default app
	if (modelValue.value[index - 1]?.default) {
		return
	}

	const after = [modelValue.value[index - 1]!]
	if (index < modelValue.value.length - 1) {
		after.push(...modelValue.value.slice(index + 1))
	}
	modelValue.value = [...before, modelValue.value[index]!, ...after]
}

/**
 * Handle element is moved down
 *
 * @param index The index of the element that is moved
 */
function moveDown(index: number) {
	const before = index > 0 ? modelValue.value.slice(0, index) : []
	before.push(modelValue.value[index + 1]!)

	const after = index < (modelValue.value.length - 2) ? modelValue.value.slice(index + 2) : []
	modelValue.value = [...before, modelValue.value[index]!, ...after]
}

/**
 * Additional status information to show to screen reader users for accessibility
 */
const statusInfo = ref('')

/**
 * ID to be used on the status info element
 */
const statusInfoId = `sorting-status-info-${(Math.random() + 1).toString(36).substring(7)}`

/**
 * Update the status information for the currently selected app
 *
 * @param index Index of the app that is currently selected
 */
function updateStatusInfo(index: number) {
	const app = modelValue.value.at(index)!
	statusInfo.value = t('theming', 'Current selected app: {app}, position {position} of {total}', {
		app: app.label ?? app.id,
		position: index + 1,
		total: modelValue.value.length,
	})
}
</script>

<template>
	<div
		:id="statusInfoId"
		aria-live="polite"
		class="hidden-visually"
		role="status">
		{{ statusInfo }}
	</div>
	<ol
		v-bind="$attrs"
		ref="listElement"
		:class="$style.appOrderSelector"
		:aria-label="t('theming', 'Navigation bar app order')">
		<AppOrderSelectorElement
			v-for="app, index in appList"
			:key="`${app.id}${renderCount}`"
			ref="selectorElements"
			:app="app"
			:aria-details="ariaDetails"
			:aria-describedby="statusInfoId"
			:is-first="index === 0 || !!appList[index - 1]!.default"
			:is-last="index === appList.length - 1"
			v-on="app.default
				? {}
				: {
					'move:up': () => moveUp(index),
					'move:down': () => moveDown(index),
					'update:focus': () => updateStatusInfo(index),
				}" />
	</ol>
</template>

<style module>
.appOrderSelector {
	width: max-content;
	min-width: 260px; /* align with NcSelect */
}
</style>
