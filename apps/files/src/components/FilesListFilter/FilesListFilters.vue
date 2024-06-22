<template>
	<Fragment>
		<FilesListFilterType ref="filters[]" />
		<FilesListFilterTime ref="filters[]" />
		<FilesListFilterName ref="filters[]" />
	</Fragment>
</template>

<script lang="ts">
import type { View } from '@nextcloud/files'
import type { PropType } from 'vue'

import { Fragment } from 'vue-frag'
import { defineComponent } from 'vue'
import FilesListFilterTime from './FilesListFilterTime.vue'
import FilesListFilterType from './FilesListFilterType.vue'
import FilesListFilterName from './FilesListFilterName.vue'

export default defineComponent({
	name: 'FilesListFilters',

	components: {
		Fragment,
		FilesListFilterTime,
		FilesListFilterType,
		FilesListFilterName,
	},

	props: {
		currentView: {
			type: Object as PropType<View>,
			required: true,
		},
	},

	watch: {
		currentView() {
			// Reset all filters on view change
			const components = this.$refs.filters as { resetFilter: () => void }[] | undefined
			components?.forEach((component) => component.resetFilter())
		},
	},
})
</script>
