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
		<tbody :style="tbodyStyle" class="files-list__tbody" data-cy-files-list-tbody>
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
			ref="tfoot"
			class="files-list__tfoot"
			data-cy-files-list-tfoot>
			<slot name="footer" />
		</tfoot>
	</table>
</template>

<script lang="ts">
import { File, Folder } from '@nextcloud/files'
import { debounce } from 'debounce'
import Vue from 'vue'
import logger from '../logger.js'

// Items to render before and after the visible area
const bufferItems = 3

export default Vue.extend({
	name: 'VirtualList',

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
			type: Array as () => (File | Folder)[],
			required: true,
		},
		itemHeight: {
			type: Number,
			required: true,
		},
		extraProps: {
			type: Object,
			default: () => ({}),
		},
		scrollToIndex: {
			type: Number,
			default: 0,
		},
	},

	data() {
		return {
			bufferItems,
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

		startIndex() {
			return Math.max(0, this.index - bufferItems)
		},
		shownItems() {
			return Math.ceil((this.tableHeight - this.headerHeight) / this.itemHeight) + bufferItems * 2
		},
		renderedItems(): (File | Folder)[] {
			if (!this.isReady) {
				return []
			}
			return this.dataSources.slice(this.startIndex, this.startIndex + this.shownItems)
		},

		tbodyStyle() {
			const isOverScrolled = this.startIndex + this.shownItems > this.dataSources.length
			const lastIndex = this.dataSources.length - this.startIndex - this.shownItems
			const hiddenAfterItems = Math.min(this.dataSources.length - this.startIndex, lastIndex)
			return {
				paddingTop: `${this.startIndex * this.itemHeight}px`,
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
		const tfoot = this.$refs?.tfoot as HTMLElement
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
		this.resizeObserver.observe(tfoot)
		this.resizeObserver.observe(thead)

		this.$el.addEventListener('scroll', this.onScroll)

		if (this.scrollToIndex) {
			this.$el.scrollTop = this.index * this.itemHeight + this.beforeHeight
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
			this.index = Math.max(0, Math.round((this.$el.scrollTop - this.beforeHeight) / this.itemHeight))
		},
	},
})
</script>

<style scoped>

</style>
