<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div
		ref="operationElement"
		class="actions__item"
		:class="{ colored: colored }">
		<div class="icon" :class="operation.iconClass" :style="{ backgroundImage: operation.iconClass ? '' : `url(${operation.icon})` }" />
		<div class="actions__item__description">
			<h3>{{ operation.name }}</h3>
			<small>{{ operation.description }}</small>
			<NcButton v-if="colored">
				{{ t('workflowengine', 'Add new flow') }}
			</NcButton>
		</div>
		<div class="actions__item_options">
			<slot />
		</div>
	</div>
</template>

<script setup lang="ts">
/* eslint vue/multi-word-component-names: "warn" */

import { t } from '@nextcloud/l10n'
import Color from 'color'
import { computed, nextTick, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'

const props = defineProps<{
	operation: Record<string, string>
	colored?: boolean
}>()

const operationElement = ref<HTMLDivElement>()
const color = ref('var(--color-main-text)')
const backgroundColor = computed(() => props.colored ? (props.operation.color || 'var(--color-primary-element)') : 'transparent')

watch(backgroundColor, async () => {
	if (backgroundColor.value === 'transparent') {
		color.value = 'var(--color-main-text)'
		return
	} else if (backgroundColor.value === 'var(--color-primary-element)') {
		color.value = 'var(--color-primary-element-text)'
		return
	}

	let bgColor = backgroundColor.value
	if (!bgColor.startsWith('#')) {
		await nextTick()
		bgColor = window.getComputedStyle(operationElement.value!).backgroundColor
	}
	try {
		const contrast = Color(bgColor).contrast(Color('#ffffff'))
		color.value = contrast > 4.5 ? '#ffffff' : '#000000'
	} catch {
		color.value = 'var(--color-main-text)'
	}
}, { immediate: true })

/**
 * Filter to apply to the icon to make it accessible on the given background color.
 */
const iconFilter = computed(() => {
	if (color.value === '#000000') {
		return 'invert(100%)'
	}
	return 'none'
})
</script>

<style scoped lang="scss">
@use "./../styles/operation.scss" as *;

.actions__item {
	color: v-bind('color');
	background-color: v-bind('backgroundColor');

	h3 {
		color: v-bind('color');
	}

	.icon {
		filter: v-bind('iconFilter');
	}
}
</style>
