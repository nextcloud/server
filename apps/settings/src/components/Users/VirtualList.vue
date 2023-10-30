<!--
	- @copyright 2023 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<table class="user-list">
		<slot name="before" />

		<thead ref="thead"
			role="rowgroup"
			class="user-list__header">
			<slot name="header" />
		</thead>

		<tbody :style="tbodyStyle"
			class="user-list__body">
			<component :is="dataComponent"
				v-for="(item, i) in renderedItems"
				:key="item[dataKey]"
				:user="item"
				:visible="(i >= bufferItems || index <= bufferItems) && (i < shownItems - bufferItems)"
				v-bind="extraProps" />
		</tbody>

		<tfoot ref="tfoot"
			v-element-visibility="handleFooterVisibility"
			role="rowgroup"
			class="user-list__footer">
			<slot name="footer" />
		</tfoot>
	</table>
</template>

<script lang="ts">
import Vue from 'vue'
import { vElementVisibility } from '@vueuse/components'
import { debounce } from 'debounce'

import logger from '../../logger.js'

Vue.directive('elementVisibility', vElementVisibility)

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
			type: Array,
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
			bufferItems,
			index: 0,
			headerHeight: 0,
			tableHeight: 0,
			resizeObserver: null as ResizeObserver | null,
		}
	},

	computed: {
		startIndex() {
			return Math.max(0, this.index - bufferItems)
		},

		shownItems() {
			return Math.ceil((this.tableHeight - this.headerHeight) / this.itemHeight) + bufferItems * 2
		},

		renderedItems() {
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

	mounted() {
		const root = this.$el as HTMLElement
		const tfoot = this.$refs?.tfoot as HTMLElement
		const thead = this.$refs?.thead as HTMLElement

		this.resizeObserver = new ResizeObserver(debounce(() => {
			this.headerHeight = thead?.clientHeight ?? 0
			this.tableHeight = root?.clientHeight ?? 0
			logger.debug('VirtualList resizeObserver updated')
			this.onScroll()
		}, 100, false))

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
		handleFooterVisibility(visible: boolean) {
			if (visible) {
				this.$emit('scroll-end')
			}
		},

		onScroll() {
			// Max 0 to prevent negative index
			this.index = Math.max(0, Math.round(this.$el.scrollTop / this.itemHeight))
		},
	},
})
</script>

<style lang="scss" scoped>
.user-list {
	--avatar-cell-width: 48px;
	--cell-padding: 7px;
	--cell-width: 200px;
	--cell-width-large: 300px;
	--cell-min-width: calc(var(--cell-width) - (2 * var(--cell-padding)));
	--sticky-column-z-index: calc(var(--vs-dropdown-z-index) + 1); // Keep the sticky column on top of the select dropdown

	// Necessary for virtual scroll optimized rendering
	display: block;
	overflow: auto;
	height: 100%;

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
		left: 0;
	}

	&__body {
		display: flex;
		flex-direction: column;
		width: 100%;
	}
}
</style>
