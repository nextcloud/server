<template>
	<table class="files-list" data-cy-files-list>
		<!-- Header -->
		<div ref="before" class="files-list__before">
			<slot name="before" />
		</div>

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
				v-for="(item, i) in renderedItems"
				:key="i"
				:visible="(i >= bufferItems || index <= bufferItems) && (i < shownItems - bufferItems)"
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
</template>

<script lang="ts">
import type { File, Folder } from '@nextcloud/files'
import { debounce } from 'debounce'
import Vue, { PropType } from 'vue'

import filesListWidthMixin from '../mixins/filesListWidth.ts'
import logger from '../logger.js'

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
			// 160px + 44px (name) + 15px (grid gap)
			return this.gridMode ? (160 + 44 + 15) : 56
		},
		// Grid mode only
		itemWidth() {
			// 160px + 15px grid gap
			return 160 + 15
		},

		rowCount() {
			return Math.ceil((this.tableHeight - this.headerHeight) / this.itemHeight) + (this.bufferItems / this.columnCount) * 2
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
		renderedItems(): (File | Folder)[] {
			if (!this.isReady) {
				return []
			}
			return this.dataSources.slice(this.startIndex, this.startIndex + this.shownItems)
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
		scrollToIndex() {
			this.index = this.scrollToIndex
			this.$el.scrollTop = this.index * this.itemHeight + this.beforeHeight
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
			logger.debug('VirtualList resizeObserver updated')
			this.onScroll()
		}, 100, false))

		this.resizeObserver.observe(before)
		this.resizeObserver.observe(root)
		this.resizeObserver.observe(thead)

		this.$el.addEventListener('scroll', this.onScroll)

		if (this.scrollToIndex) {
			this.$el.scrollTop = Math.floor((this.index * this.itemHeight) / this.rowCount) + this.beforeHeight
		}
	},

	beforeDestroy() {
		if (this.resizeObserver) {
			this.resizeObserver.disconnect()
		}
	},

	methods: {
		onScroll() {
			// Max 0 to prevent negative index
			this.index = Math.max(0, Math.floor(Math.round((this.$el.scrollTop - this.beforeHeight) / this.itemHeight) * this.columnCount))
			this.$emit('scroll')
		},
	},
})
</script>
