<template>
	<NcBreadcrumbs data-cy-files-content-breadcrumbs>
		<!-- Current path sections -->
		<NcBreadcrumb v-for="section in sections"
			:key="section.dir"
			:aria-label="t('files', `Go to the '{dir}' directory`, section)"
			v-bind="section" />
	</NcBreadcrumbs>
</template>

<script>
import NcBreadcrumbs from '@nextcloud/vue/dist/Components/NcBreadcrumbs.js'
import NcBreadcrumb from '@nextcloud/vue/dist/Components/NcBreadcrumb.js'
import { basename } from 'path'
import Vue from 'vue'

export default Vue.extend({
	name: 'BreadCrumbs',

	components: {
		NcBreadcrumbs,
		NcBreadcrumb,
	},

	props: {
		path: {
			type: String,
			default: '/',
		},
	},

	computed: {
		dirs() {
			const cumulativePath = (acc) => (value) => (acc += `${value}/`)
			return ['/', ...this.path.split('/').filter(Boolean).map(cumulativePath('/'))]
		},

		sections() {
			return this.dirs.map(dir => {
				const to = { ...this.$route, query: { dir } }
				return {
					dir,
					to,
					title: basename(dir),
				}
			})
		},
	},
})
</script>

<style lang="scss" scoped>
.breadcrumb {
	// Take as much space as possible
	flex: 1 1 100% !important;
	width: 100%;
}

</style>
