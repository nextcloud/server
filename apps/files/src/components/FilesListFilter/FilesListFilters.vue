<template>
	<Fragment>
	</Fragment>
</template>

<script lang="ts">
import type { View } from '@nextcloud/files'
import type { PropType } from 'vue'

import { Fragment } from 'vue-frag'
import { defineComponent } from 'vue'

export default defineComponent({

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
