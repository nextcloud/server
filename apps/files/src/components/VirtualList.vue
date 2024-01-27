<template>
	<div class="files-list" data-cy-files-list>
		<!-- Header -->
		<div ref="before" class="files-list__before">
			<slot name="before" />
		</div>

		<div v-if="!!$scopedSlots['header-overlay']" class="files-list__thead-overlay">
			<slot name="header-overlay" />
		</div>

		<table class="files-list__table" :class="{ 'files-list__table--with-thead-overlay': !!$scopedSlots['header-overlay'] }">
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
				:class="gridMode ? 'files-list__tbody--grid' : 'files-list__tbody--list'"
				data-cy-files-list-tbody>
				<component :is="dataComponent"
					v-for="({key, item}, i) in renderedItems"
					:key="key"
					:source="item"
					:index="i"
					v-bind="extraProps" />
			</tbody>

			<!-- Footer -->
			<tfoot v-show="isReady"
				class="files-list__tfoot"
				data-cy-files-list-tfoot>
				<slot name="footer" />
			</tfoot>
		</table>
	</div>
</template>

<script lang="ts">
import type { File, Folder, Node } from '@nextcloud/files'
import type { PropType } from 'vue'

import { debounce } from 'debounce'
import Vue from 'vue'

import filesListWidthMixin from '../mixins/filesListWidth.ts'
import logger from '../logger.js'

interface RecycledPoolItem {
	key: string,
	item: Node,
}

export default Vue.extend({
	name: 'VirtualList',

	mixins: [filesListWidthMixin],

	props: {
		dataComponent: {
			type: [Object, Function],
			required: true,
		},
		dataKey: {
			type: String,
			required: true,
		},
		dataSources: {
			type: Array as PropType<(File | Folder)[]>,
			required: true,
		},
		extraProps: {
			type: Object as PropType<Record<string, unknown>>,
			default: () => ({}),
		},
		scrollToIndex: {
			type: Number,
			default: 0,
		},
		gridMode: {
			type: Boolean,
			default: false,
		},
		/**
		 * Visually hidden caption for the table accesibility
		 */
		caption: {
			type: String,
			default: '',
		},
	},

	data() {
		return {
			index: this.scrollToIndex,
			beforeHeight: 0,
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
			if (this.gridMode) {
				return this.columnCount
			}
			return 3
		},

		itemHeight() {
			// Align with css in FilesListVirtual
			// 138px + 44px (name) + 15px (grid gap)
			return this.gridMode ? (138 + 44 + 15) : 55
		},
		// Grid mode only
		itemWidth() {
			// 160px + 15px grid gap
			return 160 + 15
		},

		rowCount() {
			return Math.ceil((this.tableHeight - this.headerHeight) / this.itemHeight) + (this.bufferItems / this.columnCount) * 2 + 1
		},
		columnCount() {
			if (!this.gridMode) {
				return 1
			}
			return Math.floor(this.filesListWidth / this.itemWidth)
		},

		startIndex() {
			return Math.max(0, this.index - this.bufferItems)
		},
		shownItems() {
			// If in grid mode, we need to multiply the number of rows by the number of columns
			if (this.gridMode) {
				return this.rowCount * this.columnCount
			}

			return this.rowCount
		},
		renderedItems(): RecycledPoolItem[] {
			if (!this.isReady) {
				return []
			}

			const items = this.dataSources.slice(this.startIndex, this.startIndex + this.shownItems) as Node[]

			const oldItems = items.filter(item => Object.values(this.$_recycledPool).includes(item[this.dataKey]))
			const oldItemsKeys = oldItems.map(item => item[this.dataKey] as string)
			const unusedKeys = Object.keys(this.$_recycledPool).filter(key => !oldItemsKeys.includes(this.$_recycledPool[key]))

			return items.map(item => {
				const index = Object.values(this.$_recycledPool).indexOf(item[this.dataKey])
				// If defined, let's keep the key
				if (index !== -1) {
					return {
						key: Object.keys(this.$_recycledPool)[index],
						item,
					}
				}

				// Get and consume reusable key or generate a new one
				const key = unusedKeys.pop() || Math.random().toString(36).substr(2)
				this.$_recycledPool[key] = item[this.dataKey]
				return { key, item }
			})
		},

		tbodyStyle() {
			const isOverScrolled = this.startIndex + this.rowCount > this.dataSources.length
			const lastIndex = this.dataSources.length - this.startIndex - this.shownItems
			const hiddenAfterItems = Math.floor(Math.min(this.dataSources.length - this.startIndex, lastIndex) / this.columnCount)
			return {
				paddingTop: `${Math.floor(this.startIndex / this.columnCount) * this.itemHeight}px`,
				paddingBottom: isOverScrolled ? 0 : `${hiddenAfterItems * this.itemHeight}px`,
			}
		},
	},
	watch: {
		scrollToIndex(index) {
			this.scrollTo(index)
		},
		columnCount(columnCount, oldColumnCount) {
			if (oldColumnCount === 0) {
				// We're initializing, the scroll position
				// is handled on mounted
				console.debug('VirtualList: columnCount is 0, skipping scroll')
				return
			}
			// If the column count changes in grid view,
			// update the scroll position again
			this.scrollTo(this.index)
		},
	},

	mounted() {
		const before = this.$refs?.before as HTMLElement
		const root = this.$el as HTMLElement
		const thead = this.$refs?.thead as HTMLElement

		this.resizeObserver = new ResizeObserver(debounce(() => {
			this.beforeHeight = before?.clientHeight ?? 0
			this.headerHeight = thead?.clientHeight ?? 0
			this.tableHeight = root?.clientHeight ?? 0
			logger.debug('VirtualList: resizeObserver updated')
			this.onScroll()
		}, 100, false))

		this.resizeObserver.observe(before)
		this.resizeObserver.observe(root)
		this.resizeObserver.observe(thead)

		if (this.scrollToIndex) {
			this.scrollTo(this.scrollToIndex)
		}

		// Adding scroll listener AFTER the initial scroll to index
		this.$el.addEventListener('scroll', this.onScroll, { passive: true })

		this.$_recycledPool = {} as Record<string, any>
	},

	beforeDestroy() {
		if (this.resizeObserver) {
			this.resizeObserver.disconnect()
		}
	},

	methods: {
		scrollTo(index: number) {
			this.index = index
			// Scroll to one row and a half before the index
			const scrollTop = (Math.floor(index / this.columnCount) - 0.5) * this.itemHeight + this.beforeHeight
			logger.debug('VirtualList: scrolling to index ' + index, { scrollTop, columnCount: this.columnCount })
			this.$el.scrollTop = scrollTop
		},

		onScroll() {
			this._onScrollHandle ??= requestAnimationFrame(() => {
				this._onScrollHandle = null
				const topScroll = this.$el.scrollTop - this.beforeHeight
				const index = Math.floor(topScroll / this.itemHeight) * this.columnCount
				// Max 0 to prevent negative index
				this.index = Math.max(0, index)
				this.$emit('scroll')
			})
		},
	},
})
</script>
