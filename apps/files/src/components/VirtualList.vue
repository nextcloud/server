<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<table class="files-list__table">
		<!-- Accessibility table caption for screen readers -->
		<caption v-if="caption" class="hidden-visually">
			{{ caption }}
		</caption>

		<!-- Header -->
		<thead ref="thead" class="files-list__thead" data-cy-files-list-thead>
			<slot name="header" />
		</thead>

		<!-- Body -->
		<tbody :style="tbodyStyle"
			class="files-list__tbody"
			data-cy-files-list-tbody>
			<component :is="dataComponent"
				v-for="({key, item}, i) in renderedItems"
				:key="key"
				:source="item"
				:index="i"
				v-bind="extraProps" />
		</tbody>

		<!-- Footer -->
		<tfoot ref="footer"
			class="files-list__tfoot"
			data-cy-files-list-tfoot>
			<slot name="footer" />
		</tfoot>
	</table>
</template>

<script lang="ts">
import type { INode, Node } from '@nextcloud/files'
import type { PropType } from 'vue'

import { defineComponent } from 'vue'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import debounce from 'debounce'
import logger from '../logger.ts'

interface RecycledPoolItem {
	key: string,
	item: Node,
}

type DataSource = INode
type DataSourceKey = keyof DataSource

export default defineComponent({
	name: 'VirtualList',

	props: {
		/**
		 * Visually hidden caption for the table accessibility
		 */
		 caption: {
			type: String,
			default: '',
		},

		/**
		 * Number of columns to render per row.
		 */
		columnCount: {
			type: Number,
			required: true,
		},

		dataComponent: {
			type: [Object, Function],
			required: true,
		},
		dataKey: {
			type: String as PropType<DataSourceKey>,
			required: true,
		},
		dataSources: {
			type: Array as PropType<DataSource[]>,
			required: true,
		},
		extraProps: {
			type: Object as PropType<Record<string, unknown>>,
			default: () => ({}),
		},

		itemHeight: {
			type: Number,
			required: true,
		},

		paddingTop: {
			type: Number,
			default: 0,
		},

		scrollToIndex: {
			type: Number,
			default: 0,
		},
	},

	setup() {
		const fileListWidth = useFileListWidth()

		return {
			fileListWidth,

			// non reactive instance properties
			onScrollHandle: undefined as number | undefined,
			recycledPool: {} as Record<string, DataSource[DataSourceKey]>,
		}
	},

	data() {
		return {
			index: this.scrollToIndex,
			footerHeight: 0,
			headerHeight: 0,
			tableHeight: 0,
			resizeObserver: null as ResizeObserver | null,
		}
	},

	computed: {
		// Wait for measurements to be done before rendering
		isReady() {
			return this.tableHeight > 0
		},

		// Items to render before and after the visible area
		bufferItems() {
			return Math.min(1, Math.round(200 / this.itemHeight))
		},

		/**
		 * The number of rows currently (fully!) visible
		 */
		visibleRows(): number {
			return Math.floor((this.tableHeight - this.headerHeight) / this.itemHeight)
		},

		/**
		 * Number of rows that will be rendered.
		 * This includes only visible + buffer rows.
		 */
		rowCount(): number {
			return this.visibleRows + (this.bufferItems / this.columnCount) * 2 + 1
		},

		/**
		 * Index of the first item to be rendered
		 * The index can be any file, not just the first one
		 * But the start index is the first item to be rendered,
		 * which needs to align with the column count
		 */
		startIndex() {
			const firstColumnIndex = this.index - (this.index % this.columnCount)
			return Math.max(0, firstColumnIndex - this.bufferItems)
		},

		/**
		 * Number of items to be rendered at the same time
		 * For list view this is the same as `rowCount`, for grid view this is `rowCount` * `columnCount`
		 */
		shownItems() {
			return this.rowCount * this.columnCount
		},

		renderedItems(): RecycledPoolItem[] {
			if (!this.isReady) {
				return []
			}

			const items = this.dataSources.slice(this.startIndex, this.startIndex + this.shownItems) as Node[]

			const oldItems = items.filter(item => Object.values(this.recycledPool).includes(item[this.dataKey]))
			const oldItemsKeys = oldItems.map(item => item[this.dataKey])
			const unusedKeys = Object.keys(this.recycledPool).filter(key => !oldItemsKeys.includes(this.recycledPool[key]))

			return items.map(item => {
				const index = Object.values(this.recycledPool).indexOf(item[this.dataKey])
				// If defined, let's keep the key
				if (index !== -1) {
					return {
						key: Object.keys(this.recycledPool)[index],
						item,
					}
				}

				// Get and consume reusable key or generate a new one
				const key = unusedKeys.pop() || Math.random().toString(36).substr(2)
				this.recycledPool[key] = item[this.dataKey]
				return { key, item }
			})
		},

		/**
		 * The total number of rows that are available
		 */
		totalRowCount() {
			return Math.ceil(this.dataSources.length / this.columnCount)
		},

		tbodyStyle() {
			// The number of (virtual) rows above the currently rendered ones.
			// start index is aligned so this should always be an integer
			const rowsAbove = Math.round(this.startIndex / this.columnCount)
			// The number of (virtual) rows below the currently rendered ones.
			const rowsBelow = Math.max(0, this.totalRowCount - rowsAbove - this.rowCount)

			return {
				paddingBlock: `${rowsAbove * this.itemHeight}px ${rowsBelow * this.itemHeight}px`,
				minHeight: `${this.totalRowCount * this.itemHeight}px`,
			}
		},
	},
	watch: {
		scrollToIndex(index) {
			this.scrollTo(index)
		},

		totalRowCount() {
			if (this.scrollToIndex) {
				this.scrollTo(this.scrollToIndex)
			}
		},

		columnCount(columnCount, oldColumnCount) {
			if (oldColumnCount === 0) {
				// We're initializing, the scroll position is handled on mounted
				logger.debug('VirtualList: columnCount is 0, skipping scroll')
				return
			}
			// If the column count changes in grid view,
			// update the scroll position again
			this.scrollTo(this.index)
		},
	},

	mounted() {
		this.resizeObserver = new ResizeObserver(debounce(() => {
			this.updateHeightVariables()
			logger.debug('VirtualList: resizeObserver updated')
			this.onScroll()
		}, 100))
		this.resizeObserver.observe(this.$el)
		this.resizeObserver.observe(this.$refs.footer as HTMLElement)

		this.$nextTick(() => {
			// Make sure height values are initialized
			this.updateHeightVariables()
			// If we need to scroll to an index we do so in the next tick.
			// This is needed to apply updates from the initialization of the height variables
			// which will update the tbody styles until next tick.
			if (this.scrollToIndex) {
				this.scrollTo(this.scrollToIndex)
			}
		})
	},

	beforeDestroy() {
		if (this.resizeObserver) {
			this.resizeObserver.disconnect()
		}
	},

	methods: {
		scrollTo(index: number) {
			if (!this.$el) {
				return
			}

			// Check if the content is smaller (not equal! keep the footer in mind) than the viewport
			// meaning there is no scrollbar
			if (this.totalRowCount < this.visibleRows) {
				logger.debug('VirtualList: Skip scrolling, nothing to scroll', {
					index,
					totalRows: this.totalRowCount,
					visibleRows: this.visibleRows,
				})
				return
			}

			// We can not scroll further as the last page of rows
			// For the grid view we also need to account for all columns in that row (columnCount - 1)
			const clampedIndex = (this.totalRowCount - this.visibleRows) * this.columnCount + (this.columnCount - 1)
			// The scroll position
			let scrollTop = this.indexToScrollPos(Math.min(index, clampedIndex))

			// First we need to update the internal index for rendering.
			// This will cause the <tbody> element to be resized allowing us to set the correct scroll position.
			this.index = index

			// If this is not the first row we can add a half row from above.
			// This is to help users understand the table is scrolled and not items did not just disappear.
			// But we also can only add a half row if we have enough rows below to scroll (visual rows / end of scrollable area)
			if (index >= this.columnCount && index <= clampedIndex) {
				scrollTop -= (this.itemHeight / 2)
				// As we render one half row more we also need to adjust the internal index
				this.index = index - this.columnCount
			} else if (index > clampedIndex) {
				// If we are on the last page we cannot scroll any further
				// but we can at least scroll the footer into view
				if (index <= (clampedIndex + this.columnCount)) {
					// We only show have of the footer for the first of the last page
					// To still show the previous row partly. Same reasoning as above:
					// help the user understand that the table is scrolled not "magically trimmed"
					scrollTop += this.footerHeight / 2
				} else {
					// We reached the very end of the files list and we are focussing not the first visible row
					// so all we now can do is scroll to the end (footer)
					scrollTop += this.footerHeight
				}
			}

			// Now we need to wait for the <tbody> element to get resized so we can correctly apply the scrollTop position
			this.$nextTick(() => {
				this.$el.scrollTop = scrollTop
				logger.debug(`VirtualList: scrolling to index ${index}`, {
					clampedIndex, scrollTop, columnCount: this.columnCount, total: this.totalRowCount, visibleRows: this.visibleRows,
				})
			})
		},

		onScroll() {
			this.onScrollHandle ??= requestAnimationFrame(() => {
				this.onScrollHandle = undefined

				const index = this.scrollPosToIndex(this.$el.scrollTop)
				if (index === this.index) {
					return
				}

				// Max 0 to prevent negative index
				this.index = Math.max(0, Math.floor(index))
				this.$emit('scroll')
			})
		},

		// Convert scroll position to index
		// It should be the opposite of `indexToScrollPos`
		scrollPosToIndex(scrollPos: number): number {
			const topScroll = scrollPos - this.paddingTop - this.headerHeight
			// Max 0 to prevent negative index
			return Math.max(0, Math.floor(topScroll / this.itemHeight)) * this.columnCount
		},

		// Convert index to scroll position
		// It should be the opposite of `scrollPosToIndex`
		indexToScrollPos(index: number): number {
			return Math.floor(index / this.columnCount) * this.itemHeight + this.paddingTop + this.headerHeight
		},

		/**
		 * Update the height variables.
		 * To be called by resize observer and `onMount`
		 */
		updateHeightVariables(): void {
			this.tableHeight = this.$el?.clientHeight ?? 0
			this.footerHeight = (this.$refs.footer as HTMLElement)?.clientHeight ?? 0

			// Get the header height which consists of table header and filters
			this.headerHeight = (this.$refs.thead as HTMLElement)?.clientHeight ?? 0
		},
	},
})
</script>
