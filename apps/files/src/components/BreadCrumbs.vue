<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcBreadcrumbs 
		data-cy-files-content-breadcrumbs
		:aria-label="t('files', 'Current directory path')">
		<!-- Current path sections -->
		<NcBreadcrumb v-for="(section, index) in sections"
			:key="section.dir"
			v-bind="section"
			dir="auto"
			:icon-text="isPersonalFiles"
			:to="section.to"
			:title="titleForSection(index, section)"
			:aria-description="ariaForSection(section)"
			@click.native="onClick(section.to)">
			<template v-if="index === 0" #icon>
				<Home :size="20"/>
			</template>
		</NcBreadcrumb>

		<!-- Forward the actions slot -->
		<template #actions>
			<slot name="actions" />
		</template>
	</NcBreadcrumbs>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'

import { translate as t} from '@nextcloud/l10n'
import { basename } from 'path'
import Home from 'vue-material-design-icons/Home.vue'
import NcBreadcrumb from '@nextcloud/vue/dist/Components/NcBreadcrumb.js'
import NcBreadcrumbs from '@nextcloud/vue/dist/Components/NcBreadcrumbs.js'
import { defineComponent } from 'vue'

import { useFilesStore } from '../store/files.ts'
import { usePathsStore } from '../store/paths.ts'

export default defineComponent({
	name: 'BreadCrumbs',

	components: {
		Home,
		NcBreadcrumbs,
		NcBreadcrumb,
	},

	props: {
		path: {
			type: String,
			default: '/',
		},
	},

	setup() {
		const filesStore = useFilesStore()
		const pathsStore = usePathsStore()
		return {
			filesStore,
			pathsStore,
		}
	},

	computed: {
		currentView() {
			return this.$navigation.active
		},

		dirs(): string[] {
			const cumulativePath = (acc: string) => (value: string) => (acc += `${value}/`)
			// Generate a cumulative path for each path segment: ['/', '/foo', '/foo/bar', ...] etc
			const paths: string[] = this.path.split('/').filter(Boolean).map(cumulativePath('/'))
			// Strip away trailing slash
			return ['/', ...paths.map((path: string) => path.replace(/^(.+)\/$/, '$1'))]
		},

		sections() {
			return this.dirs.map((dir: string) => {
				const fileid = this.getFileIdFromPath(dir)
				const to = { ...this.$route, params: { fileid }, query: { dir } }
				return {
					dir,
					exact: true,
					name: this.getDirDisplayName(dir),
					to,
				}
			})
		},

		isPersonalFiles(): string {
			return this.$route?.fullPath.startsWith('/personal-files') ? t('files', 'Personal files') : ""
		},
	},

	methods: {
		getNodeFromId(id: number): Node | undefined {
			return this.filesStore.getNode(id)
		},
		getFileIdFromPath(path: string): number | undefined {
			return this.pathsStore.getPath(this.currentView?.id, path)
		},
		getDirDisplayName(path: string): string {
			if (path === '/') {
				return t('files', 'Home')
			}

			const fileId: number | undefined = this.getFileIdFromPath(path)
			const node: Node | undefined = (fileId) ? this.getNodeFromId(fileId) : undefined
			return node?.attributes?.displayName || basename(path)
		},

		onClick(to) {
			if (to?.query?.dir === this.$route.query.dir) {
				this.$emit('reload')
			}
		},

		titleForSection(index, section) {
			if (section?.to?.query?.dir === this.$route.query.dir) {
				return t('files', 'Reload current directory')
			} else if (index === 0) {
				return t('files', 'Go to the "{dir}" directory', section)
			}
			return null
		},

		ariaForSection(section) {
			if (section?.to?.query?.dir === this.$route.query.dir) {
				return t('files', 'Reload current directory')
			}
			return null
		},

		t,
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
