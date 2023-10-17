<template>
	<table class="files-list" data-cy-files-list>
		<!-- Header -->
		<div ref="before" class="files-list__before">
			<slot name="before" />
		</div>

		<!-- Header -->
		<thead ref="thead" class="files-list__thead" data-cy-files-list-thead>
			<slot name="header"></slot>
		</thead>

		<!-- Body -->
		<RecycleScroller
			class="files-list__recycler"
			list-class="files-list__tbody"
			:items="dataSources"
			:buffer="bufferPixels"
			:item-size="itemHeight"
			:key-field="dataKey"
		>
			<template v-slot="{ item, index }">
				<component :is="dataComponent"
					:visible="true"
					:source="item"
					:index="index"
					v-bind="extraProps" />
			</template>

			<template #after>
				<!-- Footer -->
				<tfoot
					ref="tfoot"
					class="files-list__tfoot"
					data-cy-files-list-tfoot>
					<slot name="footer"></slot>
				</tfoot>
			</template>
		</RecycleScroller>
	</table>
</template>

<script lang="ts">
import { File, Folder } from '@nextcloud/files'
import Vue from 'vue'

import VueVirtualScroller from 'vue-virtual-scroller'
import 'vue-virtual-scroller/dist/vue-virtual-scroller.css'

Vue.use(VueVirtualScroller);

// Pixels to render before and after the visible area
const bufferPixels = 400

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
			bufferPixels,
		}
	},

	watch: {
		scrollToIndex() {

		},
	},

	methods: {
		onScroll() {
			// Max 0 to prevent negative index
			this.index = Math.max(0, Math.round((this.$el.scrollTop - this.beforeHeight) / this.itemHeight))
			this.$emit('scroll')
		},
	},
})
</script>

<style scoped lang="scss">
.files-list {
	display: flex;
	flex-flow: column;
	overflow: hidden;

	&__recycler {
		flex: 1;
	}
}
</style>