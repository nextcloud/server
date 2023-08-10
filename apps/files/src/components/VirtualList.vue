<template>
	<table class="files-list">
		<!-- Header -->
		<div ref="before" class="files-list__before">
			<slot name="before" />
		</div>

		<!-- Header -->
		<thead ref="thead" class="files-list__thead">
			<slot name="header" />
		</thead>

		<!-- Body -->
		<tbody :style="tbodyStyle" class="files-list__tbody">
			<tr v-for="(item, i) in renderedItems"
				:key="item[dataKey]"
				:class="{'list__row--active': isActive(i)}"
				class="list__row">
				<component :is="dataComponent"
					:active="isActive(i)"
					:source="item"
					:index="i"
					:item-height="itemHeight"
					v-bind="extraProps" />
			</tr>
		</tbody>

		<!-- Footer -->
		<tfoot ref="tfoot" class="files-list__tfoot">
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
const bufferItems = 4

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
	},

	data() {
		return {
			index: 0,
			beforeHeight: 0,
			footerHeight: 0,
			headerHeight: 0,
			tableHeight: 0,
			resizeObserver: null as ResizeObserver | null,
		}
	},

	computed: {
		renderedItems(): (File | Folder)[] {
			return this.dataSources.slice(this.startIndex, this.startIndex + this.shownItems)
		},

		startIndex() {
			return Math.max(0, this.index - bufferItems)
		},
		shownItems() {
			return Math.ceil(this.tableHeight / this.itemHeight) + bufferItems * 2
		},

		tbodyStyle() {
			const hiddenAfterItems = this.dataSources.length - this.startIndex - this.shownItems
			return {
				paddingTop: `${this.startIndex * this.itemHeight}px`,
				paddingBottom: `${hiddenAfterItems * this.itemHeight}px`,
			}
		},
	},

	mounted() {
		const before = this.$refs?.before as HTMLElement
		const root = this.$el as HTMLElement
		const tfoot = this.$refs?.tfoot as HTMLElement
		const thead = this.$refs?.thead as HTMLElement

		this.resizeObserver = new ResizeObserver(debounce(() => {
			this.beforeHeight = before?.clientHeight ?? 0
			this.footerHeight = tfoot?.clientHeight ?? 0
			this.headerHeight = thead?.clientHeight ?? 0
			this.tableHeight = root?.clientHeight ?? 0
			logger.debug('VirtualList resizeObserver updated')
		}, 100, false))

		this.resizeObserver.observe(before)
		this.resizeObserver.observe(root)
		this.resizeObserver.observe(tfoot)
		this.resizeObserver.observe(thead)

		this.$el.addEventListener('scroll', this.onScroll)
	},

	beforeDestroy() {
		if (this.resizeObserver) {
			this.resizeObserver.disconnect()
		}
	},

	methods: {
		onScroll(event: Event) {
			const target = event.target as HTMLElement
			// Max 0 to prevent negative index
			this.index = Math.max(0, Math.round((target.scrollTop - this.beforeHeight) / this.itemHeight))
		},
		isActive(index: number) {
			return index >= Math.min(this.index, bufferItems) - 1
				&& index <= (this.shownItems - bufferItems * 2) + 1
		},
	},
})
</script>

<style scoped>

</style>
