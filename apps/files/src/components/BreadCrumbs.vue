<template>
	<NcBreadcrumbs data-cy-files-content-breadcrumbs>
		<!-- Current path sections -->
		<NcBreadcrumb v-for="section in sections"
			:key="section.dir"
			:aria-label="t('files', `Go to the '{dir}' directory`, section)"
			v-bind="section"
			@click="onClick(section.to)" />
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
			const paths = this.path.split('/').filter(Boolean).map(cumulativePath('/'))
			// Strip away trailing slash
			return ['/', ...paths.map(path => path.replace(/^(.+)\/$/, '$1'))]
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

	methods: {
		onClick(to) {
			debugger
			if (to?.query?.dir === this.$route.query.dir) {
				alert('You are already here!')
			}
		},
	},
})
</script>

<style lang="scss" scoped>
.breadcrumb {
	// Take as much space as possible
	flex: 1 1 100% !important;
	width: 100%;

	::v-deep a {
		cursor: pointer !important;
	}
}

</style>
