<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Component } from 'vue'

import { useDebounceFn, useElementVisibility, useResizeObserver } from '@vueuse/core'
import { computed, ref, useTemplateRef, watch } from 'vue'
import logger from '../../../../logger.ts'

const props = withDefaults(defineProps<{
	/** Component that renders one item, it gets the item as `user` prop */
	dataComponent: Component
	/** Item property with a unique key */
	dataKey: string
	/** All items, only the visible ones are rendered */
	dataSources: Record<string, unknown>[]
	/** Height of one rendered item in pixels */
	itemHeight: number
	/** Props passed to every rendered item */
	extraProps?: Record<string, unknown>
}>(), {
	extraProps: () => ({}),
})

const emit = defineEmits<{
	/** The end of the list became visible */
	scrollEnd: []
}>()

defineSlots<{
	/** Content before the table header, e.g. a caption */
	before?: () => unknown
	/** Table header rows */
	header?: () => unknown
	/** Table footer rows */
	footer?: () => unknown
}>()

// Items to render before and after the visible area
const bufferItems = 3

const root = useTemplateRef('root')
const thead = useTemplateRef('thead')
const tfoot = useTemplateRef('tfoot')

const index = ref(0)
const headerHeight = ref(0)
const tableHeight = ref(0)

const startIndex = computed(() => Math.max(0, index.value - bufferItems))
const shownItems = computed(() => Math.ceil((tableHeight.value - headerHeight.value) / props.itemHeight) + bufferItems * 2)
const renderedItems = computed(() => props.dataSources.slice(startIndex.value, startIndex.value + shownItems.value))

const tbodyStyle = computed(() => {
	const isOverScrolled = startIndex.value + shownItems.value > props.dataSources.length
	const lastIndex = props.dataSources.length - startIndex.value - shownItems.value
	const hiddenAfterItems = Math.min(props.dataSources.length - startIndex.value, lastIndex)
	return {
		paddingTop: `${startIndex.value * props.itemHeight}px`,
		paddingBottom: isOverScrolled ? 0 : `${hiddenAfterItems * props.itemHeight}px`,
	}
})

const updateSizes = useDebounceFn(() => {
	headerHeight.value = thead.value?.clientHeight ?? 0
	tableHeight.value = root.value?.clientHeight ?? 0
	logger.debug('VirtualList resizeObserver updated')
	onScroll()
}, 100)
useResizeObserver([root, tfoot, thead], updateSizes)

const isFooterVisible = useElementVisibility(tfoot)
watch(isFooterVisible, (visible) => {
	if (visible) {
		emit('scrollEnd')
	}
})

/**
 * Track the first visible item
 */
function onScroll() {
	// Max 0 to prevent negative index
	index.value = Math.max(0, Math.round((root.value?.scrollTop ?? 0) / props.itemHeight))
}
</script>

<template>
	<table ref="root" class="user-list" @scroll="onScroll">
		<slot name="before" />

		<thead
			ref="thead"
			role="rowgroup"
			class="user-list__header">
			<slot name="header" />
		</thead>

		<tbody
			:style="tbodyStyle"
			class="user-list__body">
			<component
				:is="dataComponent"
				v-for="(item, i) in renderedItems"
				:key="item[dataKey]"
				:user="item"
				:visible="(i >= bufferItems || index <= bufferItems) && (i < shownItems - bufferItems)"
				v-bind="extraProps" />
		</tbody>

		<tfoot
			ref="tfoot"
			role="rowgroup"
			class="user-list__footer">
			<slot name="footer" />
		</tfoot>
	</table>
</template>

<style lang="scss" scoped>
.user-list {
	--avatar-cell-width: 48px;
	--cell-padding: 7px;
	--cell-width: 200px;
	--cell-width-large: 300px;
	--cell-width-groups: 380px;
	--cell-min-width: calc(var(--cell-width) - (2 * var(--cell-padding)));
	--sticky-column-z-index: calc(var(--vs-dropdown-z-index) + 1); // Keep the sticky column on top of the select dropdown

	// Necessary for virtual scroll optimized rendering
	display: block;
	overflow: auto;
	height: 100%;
	will-change: scroll-position;

	&__header,
	&__footer {
		position: sticky;
		// Fix sticky positioning in Firefox
		display: block;
	}

	&__header {
		top: 0;
		z-index: calc(var(--sticky-column-z-index) + 1);
	}

	&__footer {
		inset-inline-start: 0;
	}

	&__body {
		display: flex;
		flex-direction: column;
		width: 100%;
	}
}
</style>
